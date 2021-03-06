<?php
/**
 * Content Functions
 *
 * Functions used in the content area
 *
 * @package RoloPress
 * @subpackage Functions
 */
add_theme_support( 'post-thumbnails' ); 

add_action( 'pre_get_posts', 'rolo_post_order' );
add_action('update_postmeta', 'rolo_update_modified_date_on_meta_update');

function rolo_post_order($query) {

	if( $query->is_main_query() ) {
		
		$query->query_vars['orderby'] = 'date';
		$query->query_vars['order'] = 'DESC';

	}
}

function rolo_update_modified_taxonomy( $tax, $id ) {

	if($tax['cat'] == -1)
		$tax['cat'] = 0;

	$exists = term_exists( $tax['nova'], $tax['taxonomia'], (int) $tax['cat'] );

	if( $exists ) {
		// Se o termo já existe naquela hierarquia, marca como pertencente ao ID
		$set_object = wp_set_object_terms( $id, array( (int) $exists['term_id'] ), $tax['taxonomia'], true );
	} else {
		// Se o termo não existe naquela hierarquia, cria o termo, depois marca como pertencente ao ID
		$new_term = wp_insert_term( $tax['nova'], $tax['taxonomia'], array( 'parent' => (int) $tax['cat'] ) );
		$set_object = wp_set_object_terms( $id, array( (int) $new_term['term_id'] ), $tax['taxonomia'], true );
	}

	return;
}

function rolo_update_modified_date_on_meta_update( $meta_id ) {

	$meta = get_post_meta_by_id( $meta_id );
	$p = get_post($meta->post_id, ARRAY_A);

	$p['post_modified'] = date( 'Y-m-d H:i:s',  time() );
	$p['post_modified_gmt'] = gmdate( 'Y-m-d H:i:s',  time() );

	$id = wp_update_post( $p, true );

	if(is_wp_error( $id )) {
		wp_die( dump($id) );
	}

}

function rolo_busca_avancada( $post ) {
	dump( $post );
}

function rolo_get_avatar_image( $avatar_id, $size = array(120,120) ) {
	
	$size = apply_filters( 'post_thumbnail_size', $size );
	
	/* Usar imagem destacada caso houver */
	if ( has_post_thumbnail( $avatar_id ) ) {
		$image = get_the_post_thumbnail( $avatar_id, $size, array( 'class' => 'avatar' ) );
	} else {
		$contact_twitter = '';

	if ( has_term( 'Company', 'type', $avatar_id ) ) {
			$email = get_post_meta( $avatar_id, 'rolo_company_email', true );
		} else {
			$email = get_post_meta( $avatar_id, 'rolo_contact_email', true );
	}

	if ( !$email ) {
		$email = "contato@brasa.art.br";
	}

		$image = get_avatar( ($email), $size[0], rolo_get_twitter_profile_image($contact_twitter, ROLOPRESS_IMAGES . "/icons/gravatar-default.jpg"));
	}

	return $image;
}

add_action( 'wp_ajax_nopriv_rolo_ajax_delete', 'rolo_ajax_delete' );
add_action( 'wp_ajax_rolo_ajax_delete', 'rolo_ajax_delete' );

function rolo_ajax_delete() {

	$ids = explode(',', $_POST['data']);
	$ok = 'ok';

	foreach ($ids as $i) {
		$test = wp_delete_post( $i, false );

		if(!$test) {
			$ok = 'Ocorreu um erro';
		}
	}

	$response = array('status' => $ok, 'affected' => $ids);

	header( "Content-Type: application/json" );
	echo json_encode($response);
	exit;

}

add_action( 'wp_ajax_nopriv_rolo_ajax_edit_thumbnail', 'rolo_ajax_edit_thumbnail' );
add_action( 'wp_ajax_rolo_ajax_edit_thumbnail', 'rolo_ajax_edit_thumbnail' );

function rolo_ajax_edit_thumbnail() {

	$attid = $_POST['att'];
	$postid = $_POST['postid'];

	update_post_meta( $postid, '_thumbnail_id', $attid );
	
	$image = get_the_post_thumbnail( $postid, array(120,120) );

	header( "Content-Type: application/json" );
	echo json_encode($image);
	exit;

}

add_action( 'wp_ajax_nopriv_rolo_ajax_edit_company_other', 'rolo_ajax_edit_company_other' );
add_action( 'wp_ajax_rolo_ajax_edit_company_other', 'rolo_ajax_edit_company_other' );

function rolo_ajax_edit_company_other() {

	$vars = $_POST['data'];
	$act = $_POST['act'];
	$id = $_POST['postid'];

	if($vars[0] == "false") {
		$vars[0] = false;
	} else {
		$vars[0] = true;
	}

	if($act == 'conflito') {
		if($vars[4] != 'checked')
			$vars[4] = false;

		// update_post_meta( $id, 'rolo_conflito', $vars );

		if($vars[0])
			update_post_meta( $id, 'rolo_conflito_check', true );
		if($vars[1])
			update_post_meta( $id, 'rolo_conflito_projeto', $vars[1] );
		if($vars[2])
			update_post_meta( $id, 'rolo_conflito_desde', $vars[2] );
		if($vars[3])
			update_post_meta( $id, 'rolo_conflito_instancia', $vars[3] );
		if($vars[4])
			update_post_meta( $id, 'rolo_conflito_equacionado', true );
		if($vars[5])
			update_post_meta( $id, 'rolo_conflito_observacoes', $vars[5] );

		$safe = 'sucesso';

	} else {
		if($vars[2] != 'checked')
			$vars[2] = false;
		
		// update_post_meta( $id, 'rolo_relacao', $vars );

		if($vars[0])
			update_post_meta( $id, 'rolo_relacao_check', true );
		if($vars[1])
			update_post_meta( $id, 'rolo_relacao_local', $vars[1] );
		if($vars[2])
			update_post_meta( $id, 'rolo_relacao_apoio', true );
		if($vars[3])
			update_post_meta( $id, 'rolo_relacao_conflito', $vars[3] );

		$safe = 'sucesso';
	}

	// $response = $vars;
	$response = array('status' => $safe);

	header( "Content-Type: application/json" );
	echo json_encode($response);
	exit;

}

add_action( 'wp_ajax_nopriv_rolo_ajax_edit_contacts', 'rolo_ajax_edit_contacts' );
add_action( 'wp_ajax_rolo_ajax_edit_contacts', 'rolo_ajax_edit_contacts' );

function rolo_ajax_edit_contacts() {

	$vars = $_POST['data'];
	$postid = $_POST['company'];
	$mode = $_POST['mode'];

	if($mode == 'add') {
		// Registra na Entidade
		$contatos = get_post_meta( $postid, 'rolo_contatos', true );
		if(!in_array($vars, $contatos))
			$contatos[] = $vars;
		$meta = update_post_meta( $postid, 'rolo_contatos', $contatos );

		// Registra no Contato
		$meta = update_post_meta( $vars, 'rolo_contatos', $postid );

		$status = 'ok';
	} elseif($mode == 'remove') {
		// Remove da Entidade
		$contatos = get_post_meta( $postid, 'rolo_contatos', true );
		$keys = array_keys($contatos, $vars);
		foreach($keys as $k) {
			unset($contatos[$k]);
		}
		$meta = update_post_meta( $postid, 'rolo_contatos', $contatos );

		// Remove do Contato
		$meta = update_post_meta( $vars, 'rolo_contatos', '' );
		$status = 'ok';

	} elseif($vars['id'] == 'rolo_contact_company') {
		
		// Registra no Contato
		$p = get_page_by_title( $vars['new_value'], false, 'post' );
		$meta = update_post_meta( $vars['data'], 'rolo_contatos', $p->ID );


		// Registra na Entidade
		$contatos = get_post_meta( $p->ID, 'rolo_contatos', true );
		if(!in_array($vars, $contatos))
			$contatos[] = $vars['data'];
		$meta = update_post_meta( $p->ID, 'rolo_contatos', $contatos );
		
		$value = $vars['new_value'];
		$status = 'sucesso';

	} else {

		$meta = update_post_meta( $vars['data'], $vars['id'], esc_attr($vars['new_value']) );

		if($meta == (int) $meta && $meta != 0) {
			$status = 'sucesso';
			$value = $vars['new_value'];
		} else {
			$status = 'erro';
			$erro = $meta;
		}
	}

	$response = array( 'status' => $status, 'erro' => $erro, 'value' => $value );

	header( "Content-Type: application/json" );
	echo json_encode($response);
	exit;

}

add_action( 'wp_ajax_nopriv_rolo_ajax_autocomplete', 'rolo_ajax_autocomplete' );
add_action( 'wp_ajax_rolo_ajax_autocomplete', 'rolo_ajax_autocomplete' );

function rolo_ajax_autocomplete() {

	global $wpdb;

	$tipo = $_POST['type'];
	$term = $_POST['data'];

	if($tipo == 'nomes') {
		$response = get_posts( array( 'type' => 'contact', 'numberposts' => -1 ) );
	}
	if($tipo == 'instituicoes') {
        $response = get_posts( array( 'type' => 'company', 'numberposts' => -1 ) );
	}	

	header( "Content-Type: application/json" );
	echo json_encode($response);
	exit;

}

add_action( 'wp_ajax_nopriv_rolo_ajax_edit_company', 'rolo_ajax_edit_company' );
add_action( 'wp_ajax_rolo_ajax_edit_company', 'rolo_ajax_edit_company' );

function rolo_ajax_edit_company() {

	$vars = $_POST['data'];
	$erro = false;
	$restrict = array('rolo_conflito', 'rolo_relacao', 'rolo_company_redes' );

	if(!in_array($vars['id'], $restrict)) :

		if($vars['new_value'] == $vars['orig_value']) {
			$status = 'mesmo valor';
			$value = $vars['new_value'];

		} else {
			$meta = update_post_meta( $vars['data'], $vars['id'], esc_attr($vars['new_value']) );

			if($meta == (int) $meta && $meta != 0) {
				$status = 'sucesso';
				$value = $vars['new_value'];
			} else {
				$status = 'erro';
				$erro = $meta;
			}
		}

	else :
		$status = 'dev';

/*
		switch ($vars['id']) {
			case 'rolo_company_update':
				# code...
				break;
			
			default:
				# code...
				break;
		} */
	endif;

	$response = array( 'status' => $status, 'erro' => $erro, 'value' => $value );
 	
 	header( "Content-Type: application/json" );
	echo json_encode($response);
	exit;
}

add_action( 'wp_ajax_nopriv_rolo_ajax_edit_taxonomy', 'rolo_ajax_edit_taxonomy' );
add_action( 'wp_ajax_rolo_ajax_edit_taxonomy', 'rolo_ajax_edit_taxonomy' );

function rolo_ajax_edit_taxonomy() {

	$vars = $_POST['data'];
	$check = (int) $_POST['check'];

	// $meta = wp_get_post_terms( $vars['postid'], $vars['area'] );

	$p = get_term( $vars['val'], $vars['area'] );
	$parent = $p->parent;
	$t = array();

	if($check == 0) {
		$terms = wp_remove_object_terms( $vars['postid'], (int) $vars['val'], $vars['area'] );
		$meta = $parent;

	} else {
		$terms = wp_set_object_terms( $vars['postid'], (int) $vars['val'], $vars['area'], true );
		$check = false;
		if($parent > 0) {
			$p = get_term( $parent, $vars['area'] );
			$meta[] = $p->term_id;
			$check = true;			
		}

	}

	// $response = array( 'status' => $status, 'erro' => $erro, 'value' => $value );
 	
 	header( "Content-Type: application/json" );

 	$response = array('atualizados' => $terms, 'parents' => $meta, 'check' => $check);
	
	echo json_encode($response);
	exit;

}

/**
 * Shows appropriate title for each page
 *
 * @since 1.2
 */
function rolo_pageheader() {
    
    if (is_single() && rolo_type_is( 'company' )){
        $pagetitle = '<h2 class="page-title-light"><span class="grey">' . __('Company ','rolopress') . '</span><span class="blue">' .  __(get_the_title(),'rolopress') . "</span></h2>\n";
	} elseif (is_page()) {    
        $pagetitle = '<h2 class="page-title-light">' . __(get_the_title(),'rolopress') . "</h2>\n";
    } elseif (is_404()) {    
        $pagetitle = '<h2 class="page-title-light 404">' . __('Not Found', 'rolopress') . "</h2>\n";
	} elseif (is_home()) {    
        $pagetitle = '<h2 class="page-title-light home">' . __('All Items', 'rolopress') . "</h2>\n";
	} elseif (is_search()) {    
        $pagetitle = '<h2 class="page-title-light search">' . __('Search Results for: ', 'rolopress') . '<span class="blue">"' . rolo_search_query() . '"</spam>' . "</h2>\n";
	} elseif (is_category()) {
			$current_category = single_cat_title("", false);
			$pagedesc = category_description();
        $pagetitle = '<h2 class="page-title-light category">' . __('Items Categorized As: ', 'rolopress') . '"' . $current_category . '"' . "</h2>\n";
	} elseif (is_tag()) {
			$current_tag = single_tag_title("", false);
			$pagedesc = tag_description();
        $pagetitle = '<h2 class="page-title-light tag">' . __('Items Tagged As: ', 'rolopress') . '"' . $current_tag . '"' . "</h2>\n";
	} elseif (is_tax()) {
			global $term; 
			$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
			$pagedesc = $term->description;
        $pagetitle = '<h2 class="page-title-light taxonomy">' . __($term->name,'rolopress') . __(' List', 'rolopress') . "</h2>\n";
    } elseif (is_author()) {
			global $wp_query;
			$curauth = $wp_query->get_queried_object(); // get the authors name
		$pagetitle = '<h2 class="page-title-light author">' . __('Owned by: ', 'rolopress') . $curauth->display_name . "</h2>\n";
	} elseif (is_archive()) {
				if ( is_day() ) :
					$pagetitle = '<h2 class="page-title-light day">' . __( 'Items Created On: ', 'rolopress' ) . get_the_time(get_option('date_format')) . "</h2>\n";
				elseif ( is_month() ) :
					$pagetitle = '<h2 class="page-title-light month">' . __( 'Items Created In: ', 'rolopress' ) . get_the_time('F Y') . "</h2>\n";
				elseif ( is_year() ) :
					$pagetitle = '<h2 class="page-title-light year">' . __( 'Items Created In: ', 'rolopress' ) . get_the_time('Y') . "</h2>\n";
				endif;
    } else 
				$pagetitle = '<h2 class="page-title-light page">' . __(get_the_title(),'rolopress') . "</h2>\n";

	
// show the page title
echo $pagetitle; 

// show a description if set
if ( !empty($pagedesc) ) 
	echo ('<div class="archive-meta">' . $pagedesc . '</div>' );
}			
			
/**
 * For category lists on category archives: Returns other categories except the current one (redundant)
 *
 * @since 1.0
 */
function cats_meow($glue) {
    $current_cat = single_cat_title( '', false );
    $separator = "\n";
    $cats = explode( $separator, get_the_category_list($separator) );
    foreach ( $cats as $i => $str ) {
        if ( strstr( $str, ">$current_cat<" ) ) {
            unset($cats[$i]);
            break;
        }
    }
    if ( empty($cats) )
        return false;

    return trim(join( $glue, $cats ));
}

/**
 * Shows Categories if they exist
 *
 * @since 1.2
 */ 
function rolo_category_list() {
	if ( $cats_meow = cats_meow(', ') ) { // Returns categories other than the one queried ?>
		<span class="cat-links"><?php printf( __( 'Also assigned to %s', 'rolopress' ), $cats_meow ) ?><span class="meta-sep"> | </span></span>
<?php
	};
}

/**
 * List Tags
 *
 * @since 1.2
 */
function rolo_tag_list() {
	the_tags( '<span class="tag-links"><span class="entry-utility-prep entry-utility-prep-tag-links">' . __('Tagged: ', 'rolopress' ) . '</span>', ", ", "<span class=\"meta-sep\"> | </span>\n</span>\n\t\t\t\t\t\t" );
}				

/**
 * List notes
 *
 * @since 1.2
 */
function rolo_notes () {
	if ( comments_open() ) : 
		if (is_user_logged_in() ) { // only allow logged in users to write notes ?>
			<span class="notes-link"><?php comments_popup_link( __( 'Write a Note', 'rolopress' ), __( '1 Note', 'rolopress' ), __( '% Notes', 'rolopress' ) ) ?></span><?php
		} else { ?>
			<span class="notes-link"><?php comments_popup_link( '', __( '1 Note', 'rolopress' ), __( '% Notes', 'rolopress' ) ) ?></span><?php
		};
	endif;
}

/**
 * Show edit link if user has proper permissions
 *
 * @since 1.2
 */
function rolo_edit_item() {
		if ( current_user_can('edit_posts') ) { ?>
                <span>
				<?php if (!is_page() ) echo '<span class="meta-sep"> | </span>'; // seperates notes and edit link: not needed on pages ?>
                <?php
					if (rolo_type_is('contact')) {
						$edit_contact_page = get_page_by_title('Edit Contact');
						?>
						<a class="post-edit-link" href="<?php echo get_permalink($edit_contact_page->ID) . '?id=' . get_the_ID(); ?>" ><?php _e('Edit', 'rolopress'); ?></a>
						<?php
					}
					elseif (rolo_type_is('company')) {
						$edit_company_page = get_page_by_title('Edit Company');
						?>
						<a class="post-edit-link" href="<?php echo get_permalink($edit_company_page->ID) . '?id=' . get_the_ID(); ?>" ><?php _e('Edit', 'rolopress'); ?></a>
						<?php
					}
					else {
						edit_post_link(__('Edit','rolopress'), '','');
					};
                ?>
                </span>
            <?php
	}
};

/**
 * Entry Footer
 *
 * @since 1.2
 */
function rolo_entry_footer() { 
		global $post; ?>
		<div class="entry-meta">
			<span class="meta-prep meta-prep-author"><?php _e('By ', 'rolopress'); ?></span>
			<span class="author"><a href="<?php echo get_author_link( false, $authordata->ID, $authordata->user_nicename ); ?>" title="<?php printf( __( 'View all posts by %s', 'rolopress' ), $authordata->display_name ); ?>"><?php the_author(); ?></a></span>
			<span class="meta-sep"> | </span>
			<span class="meta-prep meta-prep-entry-date"><?php _e('Created on ', 'rolopress'); ?></span>
			<span class="entry-date"><abbr class="created" title="<?php the_time('Y-m-d\TH:i:sO') ?>"><?php the_time( get_option( 'date_format' ) ); ?></abbr></span>
		</div><!-- .entry-meta -->
					
		<div class="entry-utility group">
			<?php rolo_category_list(); ?>
			<?php rolo_tag_list(); ?>
			<?php if ($post->post_type == 'post') { rolo_notes();}  // only show notes for posts  ?>
			<?php rolo_edit_item(); ?>
		</div><!-- #entry-utility -->
<?php 
};

/**
 * Navigation above content
 *
 * @since 1.2
 */
function rolo_navigation_above() {

global $wp_query; $total_pages = $wp_query->max_num_pages; if ( $total_pages > 1 ) { ?>

				<div id="nav-above" class="navigation">
					<div class="nav-next"><?php next_posts_link(__( 'Next <span class="meta-nav">&raquo;</span>', 'rolopress' )) ?></div>
						<div class="navegacao"><?php the_paginate_link(); ?></div><!-- .navegacao -->
					<div class="nav-previous"><?php previous_posts_link(__( '<span class="meta-nav">&laquo;</span> Previous', 'rolopress' )) ?></div>
				</div><!-- #nav-above -->
<?php }
}
add_action('rolopress_before_main','rolo_navigation_above');

/**
 * Navigation below content
 *
 * @since 1.2
 */
function rolo_navigation_below() {

global $wp_query; $total_pages = $wp_query->max_num_pages; if ( $total_pages > 1 ) { ?>

				<div id="nav-below" class="navigation">
					<div class="nav-previous"><?php previous_posts_link(__( '<span class="meta-nav">&laquo;</span> Previous', 'rolopress' )) ?></div>
						<div class="navegacao"><?php the_paginate_link(); ?></div><!-- .navegacao -->
					<div class="nav-next"><?php next_posts_link(__( 'Next <span class="meta-nav">&raquo;</span>', 'rolopress' )) ?></div>
				</div><!-- #nav-below -->
<?php }
}
add_action('rolopress_after_main','rolo_navigation_below');


/**
 * Contact and Company sorter
 *
 * Handles sort options for archive pages
 *
 * @since 1.2
 */
function rolo_sorter() {
	global $query_string;

	// set sort options for Companies
	if ( rolo_type_is( 'company' ) ) {
	
		$options = get_option('rolopress_company_options');

		$rolo_company_sort_by = $options[company_sort_by];
		$rolo_company_sort_order = $options[company_sort_order];
			
			// Sort by
			if ($rolo_company_sort_by == "Name") { $rolo_company_sort_by = 'title'; }
			elseif ($rolo_company_sort_by == "Owner") {  $rolo_company_sort_by = 'author'; }
			elseif ($rolo_company_sort_by = "Date Created") { $rolo_company_sort_by = 'date'; }
			elseif ($rolo_company_sort_by = "Last Modified") { $rolo_company_sort_by = 'modified';}
			elseif ($rolo_company_sort_by = "ID") { $rolo_company_sort_by = 'ID'; }
			else $rolo_company_sort_by = 'comment_count';
		
			// Sort order
			if ($rolo_company_sort_order == "Ascending") { $rolo_company_sort_order = 'ASC'; }
			else $rolo_company_sort_order = 'DESC';
	
		$query = query_posts($query_string . "&post_type=post&meta_key=rolo_company&orderby=$rolo_company_sort_by&order=$rolo_company_sort_order");
	};
	
	// set sort options for Contacts
	if ( rolo_type_is( 'contact' ) ) {

		$options = get_option('rolopress_contact_options');
		$rolo_contact_sort_by = $options[contact_sort_by];
		$rolo_contact_sort_order = $options[contact_sort_order];
		$rolo_meta_key='rolo_contact'; //set default for rolo_meta_key
		
		
			// Sort by
			if ($rolo_contact_sort_by == "First Name") {
				$rolo_meta_key='rolo_contact_first_name';
				$rolo_contact_sort_by = 'meta_value'; }
			elseif ($rolo_contact_sort_by == "Last Name") {
				$rolo_meta_key='rolo_contact_last_name';
				$rolo_contact_sort_by = 'meta_value'; }
			elseif ($rolo_contact_sort_by == "Owner") {  $rolo_contact_sort_by = 'author'; }
			elseif ($rolo_contact_sort_by = "Date Created") { $rolo_contact_sort_by = 'date'; }
			elseif ($rolo_contact_sort_by = "Last Modified") { $rolo_contact_sort_by = 'modified';}
			elseif ($rolo_contact_sort_by = "ID") { $rolo_contact_sort_by = 'ID'; }
			else $rolo_contact_sort_by = 'comment_count';
		
			// Sort order
			if ($rolo_contact_sort_order == "Ascending") { $rolo_contact_sort_order = 'ASC'; }
			else $rolo_contact_sort_order = 'DESC';
	
		$query = query_posts($query_string . "&post_type=post&meta_key=$rolo_meta_key&orderby=$rolo_contact_sort_by&order=$rolo_contact_sort_order");
	};		
return $query;
};

/**
 * RoloPress master loop
 *
 * Currently handles most situations
 *
 * @since 1.2
 */
function rolo_loop() { ?>
<?php if ( !is_single() ) { // This class is not needed on single pages ?>
<?php global $wp_query; ?>
<?php 
	if ( is_search() && $wp_query->post_count >= 1 ) {
		echo '<input class="botao-toggle" id="group-toggle" type="button" value="Selecionar Todos" />';
		if(current_user_can( 'activate_plugins' ))
		echo '<input class="botao-exportar" id="group-exp" type="button" value="Enviar Newsletter" />';
		echo '<input class="botao-deletar" id="group-del" type="button" value="Excluir Selecionados" />';
		
	}
?>
<ul class="item-list">
<?php }; ?>
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<li>
	
	<div id="entry-<?php the_ID(); ?>" class="<?php rolopress_entry_class(); ?>">
		<?php echo (is_single() ? '' : '<a class="list-link" href="'.get_permalink().'"></a>'); ?>

		<?php rolopress_before_entry(); // Before entry hook ?>

				<div class="entry-main group">

				<?php 
					
					if (is_archive() || is_home()) { 

								if ( rolo_type_is( 'contact' ) ) { rolo_contact_header(get_the_ID());}
								if ( rolo_type_is( 'company' ) ) { rolo_company_header_list(get_the_ID());} ?>
					<?php }
					
					elseif (is_single() ) { 

						if ( rolo_type_is( 'contact' ) ) { rolo_contact_header(get_the_ID()); the_content();
								if ( is_active_sidebar("contact-under-main")){ ?>
									<div class="widget-area contact-under-main">
									<?php dynamic_sidebar("contact-under-main"); ?>
									</div> 
								<?php }
						}

						if ( rolo_type_is( 'company' ) ) { rolo_company_header(get_the_ID()); // the_content();
								if ( is_active_sidebar("company-under-main")){ ?>
									<div class="widget-area company-under-main">
									<?php dynamic_sidebar("company-under-main"); ?>
									</div> 
								<?php }
						}
					}
					
					elseif (is_search() ) { ?>
						<?php 					
                            if 
                                ( rolo_type_is( 'contact' ) ) { rolo_contact_header(get_the_ID()); }
                            elseif
                                ( rolo_type_is( 'company' ) ) { rolo_company_header_list(get_the_ID()); }
                            else { ?>
                                        <div id="entry-<?php echo basename(get_permalink());?>" class="entry-header">
                                            <?php echo '<img class="entry-icon" src=' . ROLOPRESS_IMAGES . '/icons/rolo-default.jpg />' ?>
                                            <a class="entry-title" href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a>
                                        </div>
                            <?php }
					}
									
					elseif (is_page() ) {
						the_content(); // show the page content
						
						if (is_page_template('widgets.php') || is_page_template('widgets-no-sidebar.php')) { // is this a widget page
						
							if ( is_active_sidebar("widget-page") ) { // is the widget area active ?>
								<div class="widget-area">
								<ul class="xoxo">
								<?php dynamic_sidebar("widget-page");?>
								</ul> 
								</div><!-- #widget-area -->	
								<?php }
							else {
								rolo_add_some_widgets_message(); // if not, show a message
							}
						}
					}
							
					else { ?>
								<div id="entry-<?php echo basename(get_permalink());?>" class="entry-header">
									<?php echo '<img class="entry-icon" src=' . ROLOPRESS_IMAGES . '/icons/rolo-default.jpg />' ?>
									<a class="entry-title" href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a>
								</div>
					
					<?php }; ?>
					
				</div><!-- .entry-main -->

				<?php // rolo_entry_footer(); ?>

				<?php rolopress_after_entry(); // After entry hook ?>
	
	</div><!-- #entry-<?php the_ID(); ?> -->

	<?php endwhile; ?>
	
</li>

<?php if (!is_single() ) { // not needed on single pages ?>
	</div><!-- item-list-->
<?php }; ?>


<?php else : // 404 or no search results ?>

	<div class="main-search <?php rolopress_entry_class(); ?>">

			<?php rolopress_before_entry(); // Before entry hook ?>
				<div class="entry-main">
				
					<?php 
						// on inital setup if no contacts or companies are created then 
						// the menu items produce a 404
						// This will provide instructions on how to fix
					$referring_page = $_SERVER['REQUEST_URI'];
					if ($referring_page == "/type") rolo_type_tax_message();
				
					else { rolo_404_message(); }?>

				</div>

			<?php rolopress_after_entry(); // After entry hook ?>

	</div><!-- .main-search .<?php rolopress_entry_class(); ?> -->
	
		</li><!-- #entry-0 -->

<?php endif;

}; // end rolo_loop
 
 
?>