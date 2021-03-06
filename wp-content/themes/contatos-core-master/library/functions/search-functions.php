<?php
/**
 * Search Functions
 *
 * Functions used to render the search result
 *
 * @package RoloPress
 * @subpackage Functions
 */

if($_POST['rp_submit_busca']) :

add_action( 'pre_get_posts', 'rolo_search_filter_geral', 800 );
add_action( 'pre_get_posts', 'rolo_search_filter_contact', 900 );
add_action( 'pre_get_posts', 'rolo_search_filter_company', 900 );
add_filter( 'posts_where', 'rolo_search_filter_name', 10, 2 );
add_filter( 'posts_request', 'rolo_search_all_meta_data', 10, 2 );

function rolo_search_all_meta_data($where) {

	global $wpdb;

	// Somente na busca do header
	if($_POST['busca_header'] == 'true') {

		// Insere a busca na tabela de postmeta
		$where = str_replace("FROM ".$wpdb->posts, " FROM ".$wpdb->posts.' LEFT OUTER JOIN '.$wpdb->postmeta, $where);
		// Apenas onde os IDs são iguais
		$where = str_replace("WHERE 1=1","ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE ".$wpdb->postmeta.".post_id IS NOT NULL", $where);
		// Agrupados pelos IDs
		$where = str_replace("ORDER BY ".$wpdb->posts.".post_title ASC", "GROUP BY ".$wpdb->posts.".ID ORDER BY ".$wpdb->posts.".post_title", $where);
		// Busca pelo meta_value
		$where = str_replace($wpdb->posts.'.post_title', $wpdb->postmeta.'.meta_value', $where);	
	}
	
	
	return $where;

}

function rolo_search_query() {

	$string .= 'Público: '.$_POST['busca_publicos'].' | ';

	if($_POST['busca_nome'])
		$string .= 'Nome: '.$_POST['busca_nome'].' | ';
	if($_POST['busca_municipio'])
		$string .= 'Município: '.$_POST['busca_municipio'].' | ';
	if($_POST['busca_uf'])
		$string .= 'UF: '.$_POST['busca_uf'].' | ';	
	if($_POST['busca_cargo'])
		$string .= 'Cargo: '.$_POST['busca_cargo'].' | ';
	if($_POST['busca_instituicao'])
		$string .= 'Instituição: '.$_POST['busca_instituicao'].' | ';
	
	if($_POST['tax_input']) {
		if($_POST['tax_input']['caracterizacao']) {
		   $caracterizacao = get_terms( 'caracterizacao', array('include' => $_POST['tax_input']['caracterizacao'] ) );
		   foreach($caracterizacao as $term) {
				$caracterizacoes[] = $term->name;
		   }
		   $string .= 'Caracterização Institucional: ' . implode('; ', $caracterizacoes) . ' | ';
		}
		if($_POST['tax_input']['abrangencia']) {
		   $abrangencia = get_terms( 'abrangencia', array('include' => $_POST['tax_input']['abrangencia'] ) );
		   foreach($abrangencia as $term) {
				$abrangencias[] = $term->name;
		   }
		   $string .= 'Abrangência de atuação: ' . implode('; ', $abrangencias) . ' | ';
		}
		if($_POST['tax_input']['interesse']) {
		   $interesse = get_terms( 'interesse', array('include' => $_POST['tax_input']['interesse'] ) );
		   foreach($interesse as $term) {
				$interesses[] = $term->name;
		   }
		   $string .= 'Áreas de Interesse: ' . implode('; ', $interesses) . ' | ';
		}
		if($_POST['tax_input']['impactos']) {
			$string .= 'Em situação de conflito | ';
		}
		if($_POST['tax_input']['impactos']['evento']) {
			$string .= 'Participou de evento do projeto | ';
		}
		if($_POST['tax_input']['espacos']['apoio']) {
			$string .= 'Tem apoiado/divulgado o projeto | ';
		}
	}
		

	$arr = preg_split("|\s\|\s|", $string );

	array_pop( $arr );

	$string = implode(' | ',  $arr );

	return $string;
}
 	
function rolo_search_filter_geral($query) {

	

	if( $query->is_main_query() && $_POST['busca_publicos'] ) {
		$query->is_search = true;
		$query->is_home = false;

		$query->set('post_type', 'post');
		$query->set('posts_per_page', -1); // sem paginação na busca
	}

	if($query->is_main_query() && $_POST['busca_publicos'] == 'geral') :

		$meta = array('relation' => 'AND');

		if( !empty( $_POST['busca_nome'] ) ) {
			$query->query_vars['rolo_title'] = $_POST['busca_nome'];
		}

		if( !empty( $_POST['busca_municipio'] ) ) {
			$meta[] = array( 'value' => $_POST['busca_municipio'], 'key' => 'rolo_city' );
		}

		if( !empty( $_POST['busca_uf'] ) && $_POST['busca_uf'] != 'todos') {
			$meta[] = array( 'value' => $_POST['busca_uf'], 'key' => 'rolo_uf' );
		}

		$query->query_vars['meta_query'] = $meta;
		
	endif;

	return $query;
}

function rolo_search_filter_contact($query) {

	if($query->is_main_query() && $_POST['busca_publicos'] == 'contact') :

		$meta = array('relation' => 'AND');

		$tax[] = array( 'terms' => 'contact',  'taxonomy' => 'type', 'field' => 'slug' );

		if( !empty( $_POST['busca_nome'] ) ) {
			$query->query_vars['rolo_title'] = $_POST['busca_nome'];
		}

		if( !empty( $_POST['busca_municipio'] ) ) {
			$meta[] = array( 'value' => $_POST['busca_municipio'],  'key' => 'rolo_city' );
		}

		if( !empty( $_POST['busca_uf'] ) && $_POST['busca_uf'] != 'todos') {
			$meta[] = array( 'value' => $_POST['busca_uf'],  'key' => 'rolo_uf' );
		}

		if( !empty( $_POST['busca_cargo'] ) ) {
			$meta[] = array( 'value' => $_POST['busca_cargo'],  'key' => 'rolo_contact_role' );
		}		

		if( !empty( $_POST['busca_instituicao'] ) ) {
			$meta[] = array( 'value' => $_POST['busca_instituicao'],  'key' => 'rolo_contact_company' );
		}

		$query->query_vars['meta_query'] = $meta;
		$query->query_vars['tax_query'] = $tax;

	endif;

	return $query;
}

function rolo_search_filter_company($query) {

	if($query->is_main_query() && $_POST['busca_publicos'] == 'company') :

		$meta = array('relation' => 'AND');
		$tax = array('relation' => 'AND');

		$tax[] = array( 'terms' => 'company',  'taxonomy' => 'type', 'field' => 'slug' );

		if( !empty( $_POST['busca_nome'] ) ) {
			$query->query_vars['rolo_title'] = $_POST['busca_nome'];
		}

		if( !empty( $_POST['busca_municipio'] ) ) {
			$meta[] = array( 'value' => $_POST['busca_municipio'],  'key' => 'rolo_city' );
		}

		if( !empty( $_POST['busca_uf'] ) && $_POST['busca_uf'] != 'todos') {
			$meta[] = array( 'value' => $_POST['busca_uf'],  'key' => 'rolo_uf' );
		}

		if( !empty( $_POST['tax_input'] ) ) {

			if( !empty( $_POST['tax_input']['caracterizacao'] ) ) {
				$tax[] = array( 'terms' => $_POST['tax_input']['caracterizacao'],  'taxonomy' => 'caracterizacao', 'field' => 'id', 'operator' => 'AND' );
			}
			if( !empty( $_POST['tax_input']['abrangencia'] ) ) {
				$tax[] = array( 'terms' => $_POST['tax_input']['abrangencia'],  'taxonomy' => 'abrangencia', 'field' => 'id', 'operator' => 'AND' );
			}
			if( !empty( $_POST['tax_input']['interesse'] ) ) {
				$tax[] = array( 'terms' => $_POST['tax_input']['interesse'],  'taxonomy' => 'interesse', 'field' => 'id', 'operator' => 'AND' );
			}

			if( !empty( $_POST['tax_input']['impactos'] ) ) {
				$meta[] = array( 'value' => true,  'key' => 'rolo_conflito_check' );
			}
			if( !empty( $_POST['tax_input']['espacos']['evento'] ) ) {
				$meta[] = array( 'value' => true,  'key' => 'rolo_relacao_check' );
			}			
			if( !empty( $_POST['tax_input']['espacos']['evento'] ) ) {
				$meta[] = array( 'value' => true,  'key' => 'rolo_relacao_apoio' );
			}
			
		}		

		$query->query_vars['meta_query'] = $meta;
		$query->query_vars['tax_query'] = $tax;

	endif;

	return $query;
}

function rolo_search_filter_name( $where, &$wp_query )
{
    global $wpdb;
    if ( $rolo_title = $wp_query->get( 'rolo_title' ) ) {
        $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( like_escape( $rolo_title ) ) . '%\'';
    }

    return $where;
}

endif;

?>