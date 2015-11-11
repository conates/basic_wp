<?php

//	header('Access-Control-Allow-Origin: *');
	define('FS_METHOD', 'direct');
	require_once('bfi_thumb/BFI_Thumb.php');
	require ('redbean/rb.php');
	R::setup('mysql:host='.DB_HOST.';dbname='.DB_NAME,DB_USER,DB_PASSWORD);
	require ('mandrill/Mandrill.php');



	function quitar_barra_administracion(){
		return false;
	}
	 
	add_filter( 'show_admin_bar' , 'quitar_barra_administracion');
	// Imagenes

	if ( function_exists( 'add_image_size' ) ) { 
		add_theme_support('post-thumbnails');
	}
	//Gets post cat slug and looks for single-[cat slug].php and applies it
	add_filter('single_template', create_function(
		'$the_template',
		'foreach( (array) get_the_category() as $cat ) {
			if ( file_exists(TEMPLATEPATH . "/single-{$cat->slug}.php") )
			return TEMPLATEPATH . "/single-{$cat->slug}.php"; }
		return $the_template;' )
	);
		function register_posts(){
		
		$default_post_type_opts = array(
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports' 			 => array( 'title', 'author', 'thumbnail','excerpt','editor', 'revisions', 'comments'),
				'taxonomies' => array(),
		);

		$types = array(
			/*'post' => array(
				
				'labels' => array(
					'name' => __( 'Noticias' ),
					'menu_name' => __('Noticias')
				),
				'rewrite' => array(
					'slug'=>'novedades'
				),
				//'supports' => array('title', 'author', 'thumbnail','excerpt','editor','page-attributes'),
				'hierarchical' => FALSE,
				'taxonomies' => array('post_category')
			)*/
		);
		
		foreach($types as $type=>$opts){
			register_post_type( $type,
				array_merge($default_post_type_opts, $opts )
			);
		}
	}
	add_action('init','register_posts');
	add_action( 'init', 'build_taxonomies', 0 );  
	function build_taxonomies() {  
		/*register_taxonomy(
		'post_category',
		'post',
			array(
				'hierarchical' => true,  
				'label' => 'Categoría Novedades',
				'query_var' => true,  
				'rewrite' => array('slug'=>'categoria_novedades')
			)
		);*/



	}
	register_nav_menus( array(
		'menu_home' 			=> 'Menú Principal',
		)
	);
	



	function get_post_type_label(){
		global $post;
		$data = get_post_type_object($post->post_type);
		return $data->labels->all_items;
	}
	
	/*
		obtener la imagen destacada si es que la tiene
		$args = array( 170, 200, 'bfi_thumb' => true );
	*/
	function get_thumbnail($args) 
	{	
		global $post;
			if (has_post_thumbnail( $post->ID )):
				return the_post_thumbnail($args);
			else:
				echo '<img src="http://dummyimage.com/'.$args[0].'x'.$args[1].'/cccccc/686a82.gif&text='.$args[0].' x '.$args[1].'" alt="placeholder+image">';
			endif;

	}
	/*if( function_exists('acf_add_options_sub_page') && current_user_can( 'manage_options' ))
	{
		acf_add_options_sub_page( 'Config Juegos' );
		acf_add_options_sub_page( 'Config Equipos' );
	}*/


	
	function forms(){

		if ( isset( $_REQUEST["trigger"] ) ){
			$var = $_REQUEST["trigger"];

			switch ( $var ) {
				case 'contact':
					contact();
					exit;
			}

		}

		return false;

	}
	add_action('init', 'forms');


	function contact()
	{
		unset($_POST['trigger']);
		$contact = R::dispense('contact');
		
		foreach ($_POST as $key => $value) {
			$contact->$key = $value;
		}
		$contact->created_at 	= R::isoDateTime();
		R::store($contact);
		$result = sendEmail('Fomulario de contacto Tecnipack','Contacto Tecnipack','conates.ktn@gmail.com',$contact->email,$contact->name,'thank-you');

		//createcookie(True,'Fomulario de contacto Tecnipack','Hemos recibido tu solicitud, pronto nos contactaremos con usted.');
		$data =["message" => [
					"title" => "Gracias por escribirnos.",
					"text" => "Pronto nos pondremos en contacto."
				]
				];
		wp_send_json( $data );
		
	
	}


	function exclude_single_posts( $query ) {
		if (is_category() && $query->is_main_query() && !is_admin()) {
			set_query_var('post_type',array('post' ,'product' ));
		}
		//set_query_var( 'posts_per_page', 2 );
/*
		if (is_post_type_archive('exhibitions' ) && $query->is_main_query() && !is_admin() ) { //mostrar el archive de exposiciones.
			$array_not_in = array();
			if (get_field('featured_exhibitions','option')) {
				foreach (get_field('featured_exhibitions','option') as $key => $post):
					array_push($array_not_in, $post);
				endforeach;
			}
			if (get_field('availability_exhibitions','option')) {
				foreach (get_field('availability_exhibitions','option') as $key => $post):
					array_push($array_not_in, $post);
				endforeach;
			}
			$query->set( 'post__not_in', $array_not_in );

		}






		if ( is_post_type_archive('collection' ) && $query->is_main_query() && !is_admin() && isset($_GET['letter'])){

				$args = array(
					'post_type'   		=> 'artist',
					'posts_per_page'    => -1,
					'meta_query'     => array(
										'relation' => 'OR',
											array(
											'key' => 'last_name_artist',
											'value' => '^'.$_GET['letter'],
											'compare' => 'REGEXP')
									)
				);
			
			$artists = new WP_Query( $args );
			
			$array_artist = array();
			set_query_var( 'meta_query', array(
											array(
											'key' => 'artist_collection',
											'value' => $array_artist,
											'compare' => 'IN'
											)
									)
			);
			
		}



		if ( is_post_type_archive('post' ) && $query->is_main_query() && !is_admin()){
			if ($_REQUEST['category_filter']) {
				$query->set('category_name',$_REQUEST['category_filter']);	
			}
		}


		if ( is_post_type_archive('collection' ) && $query->is_main_query() && !is_admin()){
			set_query_var( 'posts_per_page', -1 );
		}




		if (is_post_type_archive( 'releases' ) && $query->is_main_query() && !is_admin()) {
			$query->set('post__not_in',get_field('featured_publications','option'));

			if (isset($_REQUEST['year_filter'])) {
				$query->set('year',$_REQUEST['year_filter']);
			}
			if (isset($_REQUEST['author_filter'])) {
				$query->set('author',$_REQUEST['author_filter']);
			}

		}



		if ( is_post_type_archive('convene' ) && $query->is_main_query() && !is_admin()){
			set_query_var( 'posts_per_page', 10 );

			if (isset($_REQUEST['year_filter'])) {
				$query->set('year',$_REQUEST['year_filter']);
			}
		}



		if ( is_post_type_archive('artist' ) && $query->is_main_query() && !is_admin()){
			set_query_var( 'posts_per_page', 9 );
		}



		if (is_post_type_archive('videos' ) && $query->is_main_query() && !is_admin() ) { //mostrar el archive de exposiciones.
			$array_not_in = array();
			if (get_field('featured_videos','option')) {
				foreach (get_field('featured_videos','option') as $key => $post):
					array_push($array_not_in, $post);
				endforeach;
			}

			if ($_REQUEST['category_filter']) {
				$query->set('category_name',$_REQUEST['category_filter']);	
			}
			$query->set( 'post__not_in', $array_not_in );
			set_query_var( 'posts_per_page', 5 );
		}



		if (is_search() || is_category()) {
			set_query_var('post_type',array('post' ,'exhibitions' ,'releases' ,'videos' ,'artist' ,'convene','collection' ));
		}
*/
		return;
	}
	add_action( 'pre_get_posts', 'exclude_single_posts' );



	function get_all_tax_by_custom_post($tag,$post_type)
	{
		$query = new WP_Query( array( 'post_type' => $post_type) );

		$tags = array();
		foreach ($query->posts as $key => $post) {

			$terms  = get_the_terms( $post->ID, $tag );
			if ($terms) {
				foreach ($terms as $key => $term) {
					if (!in_array($term->name, $tags)) {
						array_push($tags, $term->name);
					}
				}
			}
		}

		return $tags;
	}


add_action('acf/register_fields', 'my_register_fields');

function my_register_fields()
{
    include_once('acf-range/acf-range.php');
}


	function theme_name_wp_title( $title, $sep ) {
		if ( is_feed() ) {
			return $title;
		}
		
		global $page, $paged;

		// Add the blog name
		$title .= get_bloginfo( 'name', 'display' );

		// Add the blog description for the home/front page.
		$site_description = get_bloginfo( 'description', 'display' );
		if ( $site_description && ( is_home() || is_front_page() ) ) {
			$title .= " $sep $site_description";
		}

		// Add a page number if necessary:
		if ( ( $paged >= 2 || $page >= 2 ) && ! is_404() ) {
			$title .= " $sep " . sprintf( __( 'Page %s', '_s' ), max( $paged, $page ) );
		}

		return $title;
	}
	add_filter( 'wp_title', 'theme_name_wp_title', 10, 2 );