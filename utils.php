<?php


function whoowns_get_owner_data($id=null,$provide_links=false,$extra_data=false) {
	$posts_id = array(false);
	if (is_array($id))
		$posts_id = $id;
		elseif ($id)
			$posts_id = array($id);
			elseif (!$post)
				return;
	$owner_data = array();
	foreach ($posts_id as $i=>$post_id) {
		$post = get_post($post_id);
		$owner_image_size = explode('x',get_option('whoowns_owner_image_size'));
		
		$owner_data[$i] = new stdClass();
		$owner_data[$i]->ID = $post->ID;
		$owner_data[$i]->name = $post->post_title;
		if ($provide_links)
			$owner_data[$i]->link = get_post_permalink( $post->ID );
		$res = get_post_custom($post->ID);
		foreach ($res as $name=>$r) {
			$term = str_replace('whoowns_','',$name);
			switch ($term) {
				default:
					$trad = (!is_serialized($r[0]))
						? __($r[0],'whoowns')
						: maybe_unserialize($r[0]);
					$owner_data[$i]->$term = $trad;
				break;
			}
		}
		$owner_data[$i]->image = get_the_post_thumbnail( $post->ID, $owner_image_size);
		$owner_data[$i]->type = whoowns_get_owner_type($post->ID,$provide_links);
		$owner_data[$i]->controlled_by = whoowns_get_direct_controller( $post->ID, $extra_data );
		if ($extra_data) {
			$owner_data[$i]->shareholders = whoowns_get_direct_shareholders( $post->ID, $provide_links,$extra_data );
			$owner_data[$i]->main_actors = whoowns_get_main_actors_in_network( $post->ID, $provide_links );
			#$owner_data[$i]->related_posts = whoowns_get_related_posts( $post->ID ); 
		}
	}

	return (count($owner_data)==1) ? $owner_data[0] : $owner_data;
}

function whoowns_get_owner_type($post_id,$provide_links=false) {
	$type = get_the_terms($post_id,'whoowns_owner_types');
	if (!$type)
		return false;
	$type = array_shift(array_values($type));
	$type->name = __($type->name,'whoowns');
	if ($provide_links)
		$type->link = get_term_link($type);
	return $type;
}

function whoowns_get_direct_shareholders($post_id,$provide_links=false,$full_data=false) {
	global $wpdb;
	$post_ids = (is_array($post_id))
		? $post_id
		: array($post_id);
	$res = array();
	foreach ($post_ids as $i=>$p) {
		$sql = "SELECT b.id as share_id, a.post_title as shareholder_name, b.from_id as shareholder_id, b.share, b.relative_share FROM ".$wpdb->posts." a, ".$wpdb->whoowns_shares." b WHERE a.ID=b.from_id AND to_id='$p' ORDER BY b.share DESC";
		$res[$i] = $wpdb->get_results($sql);
		if ($res[$i]) {
			if ($full_data) {
				foreach ($res[$i] as $j=>$r) {
					$data = whoowns_get_owner_data($r->shareholder_id,$provide_links);
					unset($data->ID,$data->name);
					foreach ($r as $f=>$v)
						$data->$f = $v;
					$res[$i][$j] = $data;
				}
			}
		}
	}
	if (count($res)==1)
		$res = $res[0];
	return ($res) ? $res : array();
}

function whoowns_get_direct_controller($post_id,$full_data=false) {
	$shareholders = whoowns_get_direct_shareholders($post_id,$full_data,$full_data);
	foreach ($shareholders as $s)
		if ($s->relative_share>50)
			return $s;
	return false;
}

function whoowns_get_direct_participations($post_id,$provide_links=false) {
	global $wpdb;
	$post_ids = (is_array($post_id))
		? $post_id
		: array($post_id);
	$res = array();
	foreach ($post_ids as $i=>$p) {
		$sql = "SELECT b.id as share_id, a.ID, b.share, b.relative_share FROM ".$wpdb->posts." a, ".$wpdb->whoowns_shares." b WHERE a.ID=b.to_id AND from_id='$p' ORDER BY a.post_title";
		$res[$i] = $wpdb->get_results($sql);
		if ($res[$i]) {
			foreach ($res[$i] as $j=>$r) {
				$data = whoowns_get_owner_data($r->ID,$provide_links);
				unset($data->ID);
				foreach ($r as $f=>$v)
					$data->$f = $v;
				$res[$i][$j] = $data;
			}
		}
	}
	if (count($res)==1)
		$res = $res[0];
	return ($res) ? $res : array();
}

function whoowns_get_directly_controlled($post_id) {
	$participations = whoowns_get_direct_participations($post_id,true);
	$res = array();
	foreach ($participations as $s)
		if ($s->relative_share>50)
			$res[$s->ID] = $s;
	return $res;
}

function whoowns_update_share_owners($post_id, $owners = array()) {
	global $wpdb;
	foreach ($owners as $owner) {
		if ($owner->shareholder_id) {
			$share = whoowns_set_decimal_symbol($owner->share,'.');
			if ($shares_id = $wpdb->get_var("SELECT id FROM ".$wpdb->whoowns_shares." WHERE from_id='".$owner->shareholder_id."' AND to_id='$post_id'")) {
				$wpdb->replace(
					$wpdb->whoowns_shares, 
					array( 
						'id' => $shares_id,
						'from_id' => $owner->shareholder_id,
						'to_id' => $post_id,
						'share' => $share
					),
					array(
						'%d',
						'%d',
						'%d',
						'%f'
					)
				);
			} else {
				$wpdb->insert(
					$wpdb->whoowns_shares, 
					array( 
						'from_id' => $owner->shareholder_id,
						'to_id' => $post_id,
						'share' => $share
					),
					array(
						'%d',
						'%d',
						'%f'
					)
				);
			}
			//echo $wpdb->last_error; 
		}
	}
	//After inserting the share owners, I must calculate and update their relative participation:
	$relative_shares = whoowns_calculate_relative_shares($post_id);
	$shareholders = whoowns_get_direct_shareholders($post_id);
	foreach ($shareholders as $shareholder) {
		$wpdb->update(
			$wpdb->whoowns_shares, 
			array( 
				'relative_share' => $relative_shares[$shareholder->shareholder_id]
				),
			array('id' => $shareholder->share_id),
			array(
				'%f'
			)
		);
	}
}


function whoowns_calculate_relative_shares($post_id) {
	$shareholders = whoowns_get_direct_shareholders($post_id);
	$participation_sum=$rel_participation_sum=0;
	foreach ($shareholders as $s) {
		$participation_sum += $s->share;
		$rel_participation_sum += pow($s->share,2);
	}
	$others_num = ceil((100-$participation_sum)/get_option('whoowns_relative_share_for_dummy_shareholders'));
	if ($others_num>0) {
		$others_share = (100-$participation_sum)/$others_num;
		for ($i=1;$i<=$others_num;$i++) {
			$rel_participation_sum += pow($others_share,2);
		}
	}
	foreach ($shareholders as $s)
		$relative_shares[$s->shareholder_id] = 100*(pow($s->share,2)/$rel_participation_sum);
		
	return $relative_shares;
}

function whoowns_set_decimal_symbol($value, $target_symbol='') {
	if (!$target_symbol)
		$target_symbol=get_option('whoowns_standard_decimal_symbol');
	$res = ($target_symbol=='.')
		? str_replace(',','.',$value)
		: str_replace('.',',',$value);
	return $res;
}

function whoowns_get_main_actors_in_network($postid, $provide_links=false) {
	$res = array();
	$nets = whoowns_generate_network($postid,'without_reference',true);
	//pR($nets);exit;
	foreach($nets as $dir=>$net) {
		foreach ($net as $net_postid) {
			if ($dir=='participation'){
				$controller = whoowns_get_direct_controller($net_postid);
				$participations = whoowns_get_direct_participations($net_postid);
				$check_borders = (!$participations && $controller);
			} else {
				$controlled = whoowns_get_directly_controlled($net_postid);
				$mytype = whoowns_get_owner_type($net_postid);
				$check_borders = ($controlled && $mytype->slug=='person');
			}

			if (get_post_meta($net_postid,'whoowns_is_final_controller') ||
				get_post_meta($net_postid,'whoowns_is_interchainer') || 
				$check_borders
			) {
				$res[$dir][] = $net_postid;
			}
		}
		if ($res[$dir])
			$data[$dir] = whoowns_get_owner_data($res[$dir],$provide_links);
	}
	return $data;
}

//$dir is the direction of the network from $postid: upstream is 'participation', downstream is 'composition' and both directions is 'all'
function whoowns_generate_directed_network($postid,$net=array(),$dir,$minimum_share=0) {
	global $wpdb;
	//echo $postid."-";
	if (!in_array($postid,$net)) {
		$net[] = $postid;
	} else
		return $net;

	//echo $postid;exit;
	$minimum_share_sql = ($minimum_share)
		? "AND relative_share>".$minimum_share
		: "";
	
	//Downstream: Chain of composition (shareholding composition)
	if (in_array($dir,array('composition','all'))) {
		$sql = "SELECT from_id FROM ".$wpdb->whoowns_shares." WHERE to_id='$postid' $minimum_share_sql";
		$res = $wpdb->get_results($sql);
		if ($res) {
			foreach ($res as $r) {
				$net += whoowns_generate_directed_network($r->from_id,$net,$dir,$minimum_share);
			}
		}
	}
	
	//Upstream: Chain of participations (shares ownership)
	if (in_array($dir,array('participation','all'))) {
		$sql = "SELECT to_id, relative_share FROM ".$wpdb->whoowns_shares." WHERE from_id='$postid' $minimum_share_sql";
		$res = $wpdb->get_results($sql);
		//echo "$sql<br>";
		if ($res) {
			foreach ($res as $r) {
				//echo $r->to_id.'-';
				$net += whoowns_generate_directed_network($r->to_id,$net,$dir,$minimum_share);
			}
		}
	}
	return $net;
}

function whoowns_generate_network($postid,$mode='unique',$show_dir=false) {
	$cached=true;
	if ($show_dir || !($net = whoowns_retrieve_cached($postid,'post_ids',true))) {
		$net['participation'] = whoowns_generate_directed_network($postid,array(),'participation');
		$net['composition'] = whoowns_generate_directed_network($postid,array(),'composition');
		$cached=false;
	}
	if ($mode=='unique') {
		if ($show_dir) {
			$net['participation'] = array_unique($net['participation']);
			$net['composition'] = array_unique($net['composition']);
		} elseif (!$cached) {
			$net = array_unique(array_merge($net['participation'],$net['composition']));
			whoowns_save_cached($postid,array('post_ids'=>$net));
		}
	}
	if ($mode=='without_reference')
		unset($net[0], $net['participation'][0], $net['composition'][0]);
	//pR($net);
	return $net;
}

function whoowns_get_network_relations($post_ids) {
	global $wpdb;
	if (!is_array($post_ids))
		return false;
	$post_ids = implode(',',$post_ids);
	$sql = "SELECT a.id as share_id, a.from_id as source_id, b.post_title as 'source_name', a.to_id as target_id, c.post_title as 'target_name', a.share, a.relative_share FROM ".$wpdb->whoowns_shares." a, ".$wpdb->posts." b, ".$wpdb->posts." c WHERE a.from_id=b.ID AND a.to_id=c.ID AND a.to_id IN ($post_ids) AND a.from_id IN ($post_ids)";
	$res = $wpdb->get_results($sql);
	return $res;	
}

function whoowns_prepare_network_data_for_visualization($postid='',$net='') {
	//$whoowns_visual_network_colors = get_options(whoowns_visual_network_colors);
	$whoowns_visual_network_colors = array(
		'private-enterprise'=>'#900',
		'person'=>'#090',
		'state'=>'#009',
		'Focus'=>'#000',
	);
	if (!$net && !$postid)
		return false;
	$no_net = (!$net);
	$cached=false;
	if ($postid && $no_net) {
		if (!($network_data->nodes = whoowns_retrieve_cached($postid,'nodes',true))) {
			$post_ids = whoowns_generate_network($postid);
			$net = whoowns_get_owner_data($post_ids,true,true);
		} else
			$cached=true;
	} else
		$net = whoowns_get_owner_data($net,true,true);
	if (!$cached) {
		//Generate the nodes:
		$network_data->nodes = array();
		foreach ($net as $n) {
			unset($node);
			if ($n->ID==$postid) {
				$name = $n->name;
				$type = $n->type->slug;
			}
			$node->data->id=strval($n->ID);
			$node->data->name=$n->name;
			//$node->data->is_controlled = ($n->controlled_by) ? 1 : 0;
			//$node->data->is_final_controller = ($n->is_final_controller) ? 1 : 0;
			//$node->data->is_interchainer = ($n->is_interchainer) ? 1 : 0;
			if ($n->is_final_controller)
				$node->data->rankType="finalController";
			elseif ($n->is_interchainer)
					$node->data->rankType="interChainer";
					else 
						$node->data->rankType="notRanked";
			//$node->data->typeTxt = ($n->type)
			//	? __($n->type->name,'whoowns')
			//	: __("Private enterprise",'whoowns');
			//$node->data->type = ($n->ID==$postid)
			//	? $n->type->slug."Ref"
			//	: $n->type->slug;
			$node->data->icon = ($n->type->slug)
				? $n->type->slug
				: 'private-enterprise';
			if ($n->ID==$postid)
				$node->data->icon .= "Ref";
				elseif ($n->is_final_controller)
					$node->data->icon .= "UltController";
					elseif ($n->is_interchainer)
						$node->data->icon .= "InterChainer";
			//$node->data->IPA=floatval($n->accumulated_power['IPA']*100);
			//$node->data->PA=floatval($n->PA);
			//$node->data->IPAtam=floatval($n->IPAtam);
			//$node->data->R=intval($n->rank);
			// Now, add to the array:
			$network_data->nodes[]=$node;
		}
		whoowns_save_cached($postid,array('nodes'=>$network_data->nodes));
	}
	
	//Now the edges:
	$cached=false;
	if ($postid && $no_net) {
		if (!($network_data->edges = whoowns_retrieve_cached($postid,'edges',true))) {
			if (!$post_ids)
				$post_ids = whoowns_generate_network($postid);
			$net = whoowns_get_network_relations($post_ids);
		} else
			$cached=true;
	} else
		$net = whoowns_get_network_relations($net);
	if (!$cached) {
		//Generate the edges:
		$network_data->edges = array();
		foreach ($net as $n) {
			unset($edge);
			$edge->data->id="e".$n->share_id;
			$edge->data->source=$n->source_id;
			$edge->data->target=$n->target_id;
			$edge->data->relShare=floatval($n->relative_share);
			$edge->data->color=($n->relative_share>50)
				? '#f00' : '#bbb';
			$edge->data->weightTxt=number_format($n->relative_share,2,',','.').'%';
			$edge->data->sourceName=$n->source_name;
			$edge->data->targetName=$n->target_name;
			$network_data->edges[]=$edge;
		}
		whoowns_save_cached($postid,array('edges'=>$network_data->edges));
	}
	
	
	//pR($network_data);exit;
	$network_data = json_encode($network_data);
	return $network_data;
	
	/*
	//Size of the icons is defined by the accumulated power (PA):
	$sql = "SELECT R, PA, IPA FROM assoc WHERE R IN (20,50,150,300) ORDER BY R ASC";
	$res = faz_query($sql,'','object');
	foreach ($res as $r)
		$IPAtam[]=$r->PA;
	//pR($IPAtam);exit;
	$IPAmax = $IPAtam[0];
	*/
}

function whoowns_load_network_data_callback() {
	$data = whoowns_prepare_network_data_for_visualization($_POST['post_id']);
	echo $data;
	die();
}
add_action('wp_ajax_whoowns_load_network_data', 'whoowns_load_network_data_callback');
//And below I add the same function as an accepted ajax action for not logged in users:
add_action( 'wp_ajax_nopriv_whoowns_load_network_data', 'whoowns_load_network_data_callback' );

function whoowns_load_owner_data_callback() {
	$data = whoowns_get_owner_data($_POST['post_id'], true);
	echo json_encode($data);
	die();
}
add_action('wp_ajax_whoowns_load_owner_data', 'whoowns_load_owner_data_callback');
add_action( 'wp_ajax_nopriv_whoowns_load_owner_data', 'whoowns_load_owner_data_callback' );

function whoowns_show_legends_callback() {
	$legends = array();
	$owner_types = get_terms('whoowns_owner_types', 'hide_empty=0');
	foreach ($owner_types as $i=>$type) {
		$legends['icons'][$i]['label'] = __($type->name, 'whoowns');
		$legends['icons'][$i]['img'] = $type->slug.'_'.get_option('whoowns_legends_icon_size').'.png';
	}
	$legends['colors'] = array(
		array(
			'label'=>__('Center of <i>this</i> network','whoowns'),
			'img'=>'colorRef_'.get_option('whoowns_legends_icon_size').'.png'
		),
		array(
			'label'=>__('Enterprise with interchain participation','whoowns'),
			'img'=>'colorInterChainer_'.get_option('whoowns_legends_icon_size').'.png'
		),
		array(
			'label'=>__('Ultimate controller','whoowns'),
			'img'=>'colorUltController_'.get_option('whoowns_legends_icon_size').'.png'
		)
	);
	$legends['arrows'] = array(
		array(
			'label'=>__('Control over the enterprise (>50%)','whoowns'),
			'img'=>'arrowControl_'.get_option('whoowns_legends_icon_size').'.png'
		),
		array(
			'label'=>__('Participation of less than 50%','whoowns'),
			'img'=>'arrowNoControl_'.get_option('whoowns_legends_icon_size').'.png'
		)
	);
	switch(get_option('whoowns_legends_format')) {
		case 'horizontal':
			?>
			<h2><?=__('Legends','whoowns')?></h2>
			<table id='cy-legends-table'>
				<tr id='cy-legends-table-subtitle'>
					<th colspan="6"><?=__('Icons','whoowns')?></th>
					<th colspan="6"><?=__('Colors','whoowns')?></th>
					<th colspan="4"><?=__('Arrows','whoowns')?></th>
				</tr>
				<tr id='cy-legends-table-content'>
					<?php
					foreach ($legends as $type=>$sublegends) {
						$first=' whoowns-first';
						foreach ($sublegends as $legend) {
							?>
							<td class="whoowns-icon<?=$first?>"><img src="<?=plugins_url('/images/'.$legend['img'], __FILE__ )?>" /></td>
							<td class="whoowns-label"><?=$legend['label']?></td>
						<?php
							if ($first)
								$first='';
						}
					}
					?>
				</tr>
			</table>
			<?php
		break;
		case 'vertical':
		break;
	}
	
	die();
}
add_action('wp_ajax_whoowns_show_legends', 'whoowns_show_legends_callback');
add_action( 'wp_ajax_nopriv_whoowns_show_legends', 'whoowns_show_legends_callback' );

function whoowns_autocomplete_callback() {
	global $wpdb;
	
	$term=$_REQUEST['term'];
	$sql = "SELECT post_title as label, ID as value FROM ".$wpdb->prefix."posts WHERE post_status='publish' AND post_type='whoowns_owner' AND post_title like '%$term%' order by post_title";
	$res = $wpdb->get_results($sql,OBJECT);
	echo json_encode($res);

	die(); // this is required to return a proper result
}
add_action('wp_ajax_whoowns_autocomplete', 'whoowns_autocomplete_callback');
add_action('wp_ajax_nopriv_whoowns_autocomplete', 'whoowns_autocomplete_callback');

function whoowns_select_owners($filters,$s='',$orderby,$order,$page=0) {
	global $wpdb;
	
	$args = array(
		'posts_per_page' => get_option('whoowns_owners_per_page'),
		'post_type' => 'whoowns_owner', 
		'fields' => 'ids'
	);
	
	if (!$filters && !$s) {
		$filters='ranked';
		if (!$orderby) {
			$orderby = 'whoowns_PA';
			$order = 'DESC';
		}
	} elseif (!$orderby) {
		$orderby = 'name';
	}
	/*elseif (is_array($filters)) {
		$args['post__in'] = $filters;
		unset($filters);
	}*/
		
	if ($s) {
		if (strpos($s,':')!==false) {
			list($key,$values)=explode(':',$s);
			if ($key == 'owner_ids') {
				$owner_ids = explode('|',$values);
				$args['post__in'] = $owner_ids;
			}
		} else {
			$args['s'] = $s;
			if ($tmp = intval(trim(str_replace(array('.','/',',','-'),'',$s))))
				$args['meta_query'][] = array(
					'key' => 'whoowns_legal_registration',
					'value' => $tmp
				);
		}
	}
	if ($page)
		$args['paged'] = $page;
	switch ($orderby) {
		case 'name':
			$args['orderby'] = 'title';
			$args['order'] = 'ASC';
		break;
		default:
			$args['meta_key'] = $orderby;
			$args['orderby'] = 'meta_value_num';
			$args['order'] = 'DESC';
		break;
	}
	
	if (!is_array($filters))
		$filters = explode(',',$filters);
	foreach ($filters as $filter) {
		switch ($filter) {
			case 'ranked':
				$args['meta_query'][] = array(
					'key' => 'whoowns_rank',
					'value' => 0,
					'compare' => '>'
				);
			break;
			case 'person':
			case 'private-enterprise':
			case 'state':
				if (!$args['tax_query']['relation'])
					$args['tax_query']['relation'] = 'OR';
				$args['tax_query'][] = array(
					'taxonomy' => 'whoowns_owner_types',
					'field' => 'slug',
					'terms' => $filter
				);
			break;
		}
	}
	#pR($args);
	$res = new WP_Query( $args );
	if (!$res->posts)
		return false;
	
	$owners = array();
	foreach ($res->posts as $i=>$r) {
		$owners[] = whoowns_get_owner_data($r,true);
	}
	$return->found_posts = $res->found_posts;
	$return->max_num_pages = $res->max_num_pages;
	$return->owners = $owners;
	return $return;
}

function pR($txt) {
	?><pre><?php
	print_r($txt)
	?></pre><hr><?php
}

function whoowns_retrieve_cached($postid,$fields,$decoded=false) {
	global $wpdb;

	if (!$postid || !$fields)
		return false;
	$fields_sql = (is_array($fields))
		? implode(',',$fields)
		: $fields;
	$sql = "SELECT $fields_sql FROM ".$wpdb->whoowns_networks_cache." WHERE post_id='$postid'";
	if (!($res = $wpdb->get_results($sql)))
		return false;
	if ($decoded) {
		if (is_array($fields)) {
			foreach ($fields as $field)
				$cached->$field = json_decode($res[0]->$field,true);
		} else
			$cached = json_decode($res[0]->$fields,true);
	} else {
		$cached = $res[0]->$fields;
	}
	return $cached;
}

function whoowns_save_cached($postid,$values) {
	global $wpdb;
	foreach ($values as $col=>$val)
		if (is_array($val) || is_object($val))
			$values[$col]=json_encode($val);
	$sql = "SELECT post_id FROM ".$wpdb->whoowns_networks_cache." WHERE post_id='$postid'";
	$res = $wpdb->get_results($sql);
	$res = ($res[0])
		? $wpdb->update( $wpdb->whoowns_networks_cache, $values, array('post_id'=>$postid))
		: $wpdb->insert( $wpdb->whoowns_networks_cache, array_merge(array('post_id'=>$postid),$values));
	return $res;
}

function whoowns_get_network_related_news($postid='', $net='') {
	if (!$net && !$postid)
		return false;
	$no_net = (!$net);
	$cached=false;
	if ($postid && $no_net) {
		if (!($names = whoowns_retrieve_cached($postid,'names',true))) {
			$post_ids = whoowns_generate_network($postid);
			$net = whoowns_get_owner_data($post_ids);
		} else
			$cached=true;
	} else
		$net = whoowns_get_owner_data($net);
	if (!$cached) {
		//Generate the names:
		foreach ($net as $n) {
			$names[]=whoowns_clean_owner_name($n->name);
		}
		whoowns_save_cached($postid,array('names'=>$names));
	}
	
	/*$sql = "SELECT DISTINCTROW nome FROM assoc WHERE receita>0 AND id!=$id AND id IN (".implode(',',$p2).") ORDER BY receita DESC LIMIT 5";
	$empresasNoticias = faz_query($sql,'','object');
	$nomelimpo=array($nome);
	if ($empresasNoticias)
		foreach ($empresasNoticias as $e)
			$nomelimpo[] = $e->nome;

	foreach ($nomelimpo as $j=>$nm)
		$nomelimpo[$j] = '"'.whoowns_clean_owner_name($nm).'"';*/
	//$noticias = simplexml_load_file("http://news.google.com/news?cf=all&ned=pt-BR_br&hl=pt-BR&q=\"$nome\"&output=rss");
	//date_default_timezone_set('America/Sao_Paulo');
	$nomelimpotxt = implode(" | ",$nomelimpo);
	$nomesitesBuscatxt = implode(", ", array_keys($sitesBusca));
	$itens=array();
	foreach ($sitesBusca as $siteBusca) {
		$urlBusca = "http://br.bing.com/search?q=($nomelimpotxt) site:$siteBusca&format=rss";
		$noticias = simplexml_load_file($urlBusca);
		echo "<!--$urlBusca-->".chr(10);
		if ($noticias->channel->item) {
			$k=0;
			foreach($noticias->channel->item as $item) {
				if (!in_array($item->link,$sitesVetados) && strpos($item->link,$siteBusca)!==false) {
					$k++;
					$data = date('d/m/Y à\s H\hi e', strtotime($item->pubDate));
					$link = $item->link;
					$titulo = $item->title;
					$desc = $item->description;
		    		$itens[strtotime($item->pubDate)]="
		    		<h2><a target='_blank' href='$link'>$titulo</a></h2>
		    		<p class='data'>$data</p>
		    		<p>$desc</p>
		    		<p class='leiamais'><a target='_blank' href='$link'>ler notícia...</a></p>";
		    		if ($k==$numNoticiasPorSite)
		    			break;
		   		}
		   	}
	    }
	}
	if (!$itens) {
		$itens[]="<br />Não há notícias sobre \"<i>$nome</i>\" e empresas com receita nas quais tem participação direta ou indireta .";
	}
	krsort($itens);
	?>
	<div class="<?=$divclasstxt?>">
		<h1>Últimas notícias relacionadas a <?=$nome?> e suas participações</h1>
		<h5 style="padding-top:0">Pesquisa do Bing nas fontes: <?=$nomesitesBuscatxt?></h5>
		<div id='noticiasRSS'>
			<?=implode(chr(10),$itens)?>
		</div>
	</div>
<?php 
}

function whoowns_clean_owner_name($name) {
	return trim(str_replace(array(' SA ',' LTDA ',' LTD ',' INC ', ' SARL '),'',str_replace(array(',',';',':','-','_','.','/','(',')'),'',$name.' ')));
}

function dummy_for_translators() {
	__('State','whoowns');
	__('Private enterprise','whoowns');
	__('Person','whoowns');
	
}
?>
