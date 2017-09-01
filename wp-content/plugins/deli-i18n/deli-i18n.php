<?php 
/*
Plugin Name: Deli Internationalisation 
Description: Textes et traductions. Supporte les posts, les wordings, les categories, menus
Version: 201708
Author: delicyus
Author URI: http://delicyus.com
License: GPL2
*/

// 	@exemple 
//  MENUS
//	echo do_shortcode('[get_menu menu="Menu primaire"]');

// 	CATEGORIES
// 	echo $I18n -> ll_( [CATEGORY SLUG] ); // renvoi le name
// 	echo $I18n -> ll_( [CATEGORY SLUG] , 'description');

// 	POST
// 	echo $I18n -> ll_($post -> post_name); // post_title
// 	echo $I18n -> ll_($post -> post_name , 'post_content');

// 	WORDING
// 	echo $I18n -> ll_('bonjour'); // post_title
// 	echo $I18n -> ll_('bonjour' , 'post_content');


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

if(!class_exists("Deli_I18n_Plugin")){
	class Deli_I18n_Plugin 
	{
		
		function __construct()
		{

			// CONFIG des langues supportees
			$this -> set_config_wording();
  			
			// HOOKS INIT
			add_action( 'init', array( $this , 'set_locale') , 11 ); // (requis)
			add_action( 'p2p_init', array($this,'register_connection_types') );		

			// HOOKS wording
			add_action( 'init', array( $this , 'create_post_type_wording' ) , 0 ); 
			add_action( 'init', array($this , 'create_wording_taxo') , 0);
			add_action( 'langue_add_form_fields', array($this,'langue_taxonomy_custom_fields'), 10, 2 );
			add_action( 'langue_edit_form_fields', array($this,'langue_taxonomy_custom_fields'), 10, 2 );
			// add_action( 'edited_langue', array($this , 'save_taxonomy_custom_fields'), 10, 2 );

			// HOOKS Categories
			add_filter('deleted_term_taxonomy', array($this,'categories_remove_i18n_options') );
			add_filter('edited_terms', array($this,'categories_update_i18n_options') );
			add_filter('edit_category_form', array($this,'render_categories_i18n_options'));	
 
			// HOOKS menus  
			add_shortcode('get_menu', array($this,'get_menu'));
		} 


		// SET UP INIT 
		//
		//

		// CONFIG PLUGIN
		private function set_config_wording(){
			$this -> config_wording = new stdClass();
			$this -> config_wording -> metas_requises = array();
			$this -> config_wording -> metas_langues_slugs = array(
				'en' , 
				'fr' , 
				'it' , 
				'de' , 
			);
			$this -> config_wording -> metas_langues_codes = array(
				'en' => 'us_US', 
				'fr' => 'fr_FR', 
				'it' => 'it_IT', 
				'de' => 'de_DE', 
			);

			$this -> config_wording -> metas_labels = array(
				'en' => "Anglais" ,
				'fr' => "Fran&ccedil;ais",
				'it' => "Italien",				
				'de' => 'Allemand', 
			);			
			$this -> config_wording -> metas_langues_long_slugs = array(
				'en' => "anglais" ,
				'fr' => "francais",
				'it' => "italien",				
				'de' => 'allemand', 
			);			
		}

		// SET CURRENT LANGUAGE depuis REQUEST
		public function set_locale() {

			// requis 
			if (!session_id()) 
				session_start(); 
			
			// The string requested need to be a registered code in config too
			$is_registered_lang = in_array( $_REQUEST['lang'] , $this -> config_wording -> metas_langues_slugs);

			if( isset( $_REQUEST['lang'] ) && $is_registered_lang ){

				$WPLANG = $this -> config_wording -> metas_langues_codes[$_REQUEST['lang']];

				// Set up global persistant
		 		setlocale(LC_ALL, $WPLANG . '.utf8');
	 			// setlocale(LC_ALL, 'en_EN.utf8');

				$_SESSION['WPLANG'] 	= $WPLANG;
				$_SESSION['CODE_LANG'] 	= $_REQUEST['lang'];
			}

			// SET Var de la class
			$this -> set_locale 		= $WPLANG;
			$this -> set_locale_slug 	= $_SESSION['CODE_LANG'];
			
			return $WPLANG;
		}

		// REGISTER CUSTOM POST TYPE
		public 	function create_post_type_wording() {

	    	register_post_type( 'wording',
		        array(
		            'labels' 		=> array(
		                'name' 			=> __( 'Textes du site' ),
		                'singular_name' => __( 'Texte du site' ) ,
		                'add_new_item' 	=> __( 'Ajouter un Texte' ) ,
		                'new_item' 		=> __( 'Nouveau Texte' ) ,
		                'edit_item'	 	=> __( 'Modifier Texte' )
		            ),
		        'menu_icon' 		=> 'dashicons-translation',
		        'menu_position' 	=> 102,
		        'show_ui' 			=> true,
		        'public' 			=> false,
		        'has_archive' 		=> false,
				'supports' 			=> array('title' , 'editor' ),
				'taxonomies' 		=> array( 'langue' ),
				// 'register_meta_box_cb'	=> array( $this , 'add_wording_metas_box') ,
		        )
		    );
	    }

		// CONFIG P2P
		public function register_connection_types() {
			if(!function_exists("p2p_register_connection_type"))
				return false;

			p2p_register_connection_type( array(
				'name' 			=> 'posts_to_wording',
				'from' 			=> 'post',
				'to' 			=> 'wording',
				'sortable' 		=> 'any'	,
				'reciprocal' 	=> true,
	        	'admin_column' 	=> true					
			) );
			p2p_register_connection_type( array(
				'name' 			=> 'wording_to_wording',
				'from' 			=> 'wording',
				'to' 			=> 'wording',
				'sortable' 		=> 'any'	,
				'reciprocal' 	=> true,
	        	'admin_column' 	=> true					
			) );
		}

		// REGISTER TAXONOMIE CUSTOM "langues" pour manipuler les "wording" par langue
		public function create_wording_taxo() {

			// Add new taxonomy, make it hierarchical (like categories)
			$labels = array(
				'name'              => _x( 'Langues', 'taxonomy general name', 'textdomain' ),
				'singular_name'     => _x( 'Langue', 'taxonomy singular name', 'textdomain' ),
				'search_items'      => __( 'Rechecher Langues', 'textdomain' ),
				'all_items'         => __( 'Toutes les Langues', 'textdomain' ),
				'parent_item'       => __( 'Langue Parente', 'textdomain' ),
				'parent_item_colon' => __( 'Langue Parente:', 'textdomain' ),
				'edit_item'         => __( 'Editer Langue', 'textdomain' ),
				'update_item'       => __( 'Update Langue', 'textdomain' ),
				'add_new_item'      => __( 'Ajouter New Langue', 'textdomain' ),
				'new_item_name'     => __( 'Nouvelle Langue Name', 'textdomain' ),
				'menu_name'         => __( 'Langue', 'textdomain' ),
			);

			$args = array(
				'hierarchical'      => false,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'public'         	=> false,
				'description' 		=> "Classement par langue des wordings",
			);
			register_taxonomy( 'langue', array( 'wording' ), $args );	

			// To avoid conflict with custom post type registred
			// see 	https://codex.wordpress.org/Function_Reference/register_taxonomy#Usage
			register_taxonomy_for_object_type( 'langue', 'langue' );
		}
	    // Add a custom display to the taxonomy edit/add form 
	    // see https://sabramedia.com/blog/how-to-add-custom-fields-to-custom-taxonomies
	    // https://section214.com/2016/01/adding-custom-meta-fields-to-taxonomies/
		public function langue_taxonomy_custom_fields($tag) {  

		  	// Check for existing taxonomy meta for the term you're editing  
		    $t_id = $tag->term_id; // Get the ID of the term you're editing  
		    $term_meta = get_option( "taxonomy_term_$t_id" ); // Do the check  
		    ?>  
		    <tr>
		    	<td colspan="2">
					<?php 
		        	if(!in_array( $tag -> slug , $this -> config_wording -> metas_langues_slugs) ){
			    		?>
		        		<h2><span class="dashicons dashicons-warning"></span> Attention : Le champ <em>Identifiant</em> n'est pas valide</h2>
		        		<h3>Les traductions ne fonctionneront pas.</h3>
		        		<p>Corrigez le champ <em>Identifiant</em> avec un code  </p>
						<?php
		        	}
					?>	        
		    		<p>choisi parmi cette liste : </p>
	        		<ul>
		        		<?php 	
						foreach($this -> config_wording -> metas_langues_slugs as $langue_slug) {
							?>
							<li>
								Pour <em><?php echo $this -> config_wording -> metas_labels[$langue_slug];?></em> 
								saisissez <code><?php echo $langue_slug; ?></code>
							</li>
							<?php
						}
						?>
	        		</ul>		    		
		    	</td>
		    </tr>
	    <?php  
	    } 

		// CATEGORIES SET UP 
		// REGISTER des custom options pour les categories 
		private function define_categories_custom_options(){
			define('categories_i18n_options', 'categories_option_i18n_options'); // the option name (requis)
		}
		// RENDER ADMIN (the form)
		public function render_categories_i18n_options($tag) {
		    $tag_extra_fields = get_option(categories_i18n_options);
		    ?>
		    <div class="wrapper-not-native">
			    <h3><span class="dashicons dashicons-translation"></span> Traductions</h3>
				<?php 
				foreach ($this -> config_wording -> metas_langues_slugs as $lang_slug) {

					// On ne geres pas le FR dans les traductions
					if($lang_slug=='fr')
						continue;
					?>
					<table class="form-table" >
				        <tr class="form-field">
				        	<td colspan="2">
				        	<h3>
				        		<?php  echo $this -> config_wording -> metas_labels [$lang_slug];?>
			        		</h3>
				        	</td>
				        </tr>

				        <tr class="form-field">
				            <th>
				            	<label for="traduction-<?php echo $lang_slug;?>-<?php echo $tag->term_id;?>">Nom</label>
			            	</th>
				            <td>
				            	<input
				            	name="traduction-<?php echo $lang_slug;?>-<?php echo $tag->term_id;?>"
				            	id="traduction-<?php echo $lang_slug;?>-<?php echo $tag->term_id;?>"
				            	type="text"
				            	size="40"
				            	aria-required="false"
				            	value="<?php echo $tag_extra_fields[$tag->term_id][$lang_slug]; ?>" />
			            	</td>
				        </tr>

				        <tr class="form-field">
				            <th>
				            	<label for="traduction-description-<?php echo $lang_slug;?>-<?php echo $tag->term_id;?>">Description</label>
			            	</th>
				            <td>
					            <textarea 
					            name="traduction-description-<?php echo $lang_slug;?>-<?php echo $tag->term_id;?>" 
					            id="traduction-description-<?php echo $lang_slug;?>-<?php echo $tag->term_id;?>" 
					            rows="5"><?php echo $tag_extra_fields[$tag->term_id][$lang_slug.'-description']; ?></textarea>
			            	</td>
				        </tr>	
					</table>
					<?php
				}
			 	?>
		    </div>
		    <?php
		}
		// SUBMIT ADMIN
		public function categories_update_i18n_options($term_id) {
			if($_POST['taxonomy'] == 'category'):
				$tag_extra_fields = get_option(categories_i18n_options);
				foreach ($this -> config_wording -> metas_langues_slugs as $lang_slug) {
				    $tag_extra_fields[$term_id][$lang_slug] 		= strip_tags($_POST['traduction-'.$lang_slug.'-'.$term_id]);
				    $tag_extra_fields[$term_id][$lang_slug.'-description'] 		= strip_tags($_POST['traduction-description-'.$lang_slug.'-'.$term_id]);
				}
			    update_option(categories_i18n_options, $tag_extra_fields);
		  	endif;
		}
		// UPDATE when a category is removed
		public function categories_remove_i18n_options($term_id) {
		  	if($_POST['taxonomy'] == 'category'):
			    $tag_extra_fields = get_option(categories_i18n_options);
			    unset($tag_extra_fields[$term_id]);
			    update_option(categories_i18n_options, $tag_extra_fields);
	  		endif;
		}	





		// RENDERING 
		//
		//


		// RENDER Menu according to current language
		public function render_menu_switch_lang(){
			?>
			<div class="langue-menu">
				<ul>
					<?php
					foreach( $this -> config_wording -> metas_langues_slugs as $meta_slug ){

						$actual_link 	= "http://" . $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
						$actual_link 	= $this -> removeqsvar($actual_link, 'lang');
						$actual_link 	.= "?";
						$link 			=  $actual_link . 'lang=' . $meta_slug;

						$meta_display_name = $this -> config_wording -> metas_langues_long_slugs[$meta_slug];
						$meta_display_name = $this -> ll_( strtolower( $meta_display_name ));
						
						?>
						<li>
							<a
							<?php $this -> menu_highlight_current_lang($meta_slug);?>
							href="<?php echo $link;?>"> 
							<?php echo ucfirst( $this -> ll_( strtolower( $meta_display_name ) ) );?> 
							<?php if($this -> set_locale_slug===$meta_slug) echo ' + '; ?></a>
						<?php
						}
						?>
					</li>

				</ul>
			</div>
		<?php
		}	
		// HELPER : clean up le param "lang" de l'URL apres changement de langue
		private function removeqsvar($url, $varname) {
		    // Parse URL
		    list($urlpart, $qspart) = array_pad( explode('?', $url), 2, '');
		    parse_str($qspart, $qsvars);
		    // Retire le param dans l'URL
		    unset( $qsvars[$varname] );
		    // Rebuild l'URL
		    $newqs = http_build_query($qsvars);
		    // Si l'url a dautres params a preserver	
		    if(sizeof($qsvars)>=1)
		    	$urlpart .= '?';

		    return $urlpart . $newqs;
		}
		// HELPER : Highlight current dans menu lang
		private function menu_highlight_current_lang($meta_slug){
			if( $this -> set_locale_slug === $meta_slug ) 
				echo ' class="langue-current" ';
		}

		// 	Return traduction d'un field avec langue courante 
		// 	@params  $field : le champ du post ou la custom option de la cat dont on veux la valeur
		public function ll_( $slug , $field ){

			// requis
			if('' == $slug) return $slug;
			// defaut 
			$is_= 'post';
			
			// Is there a post ?
			$post = $this -> post_by_slug( $slug );

			// Is It a category ? 
			if(!$post) 
				$category = get_category_by_slug( $slug );
			if($category)
				$is_ = "category";

			// Ni Category ni Post n'ont ete trouvé
			if(!$category  && !$post) 
				return $slug;

			switch ($is_) {
				case 'post':
					// Return post 
					if($post) 
						return $this -> l_post($post, $field );
					break;
				case 'category':
					// Return category 
					if($category) 
						return $this -> l_category($category , $field);
					break;
			}
		}

		// Return $original traduit in current lang
		// Supporte tous les types de posts, les categories
		private function traduire_l_( $original ){

			// Is it a post ?
			if(isset( $original -> ID))
				$is_ = 'post';

			// Is it a category ?
			if(isset($original -> term_id))
				$is_ = 'category';

			if(''==$is_)
				return false;

			switch ($is_) {
				case 'post':
					return $this -> traduire_post($original);
					break;
				case 'category':
					return $this -> traduire_category($original);
					break;
				}	
		}	

		// Return {post} avec son slug ou false
		private function post_by_slug($slug){

			// cherche le post
			$post_exist_args = array(
			  'name'      		=> $slug , // The name (slug) for your post
			  'post_type'     	=> array('wording','post') ,// Default 'post'.
			);
			$post_exist = get_posts( $post_exist_args );

			// Si on a trouvé un post	
			if ( sizeof($post_exist)>=1 )
				return $post_exist[0];	
			
			// si pas de post
			return false;	
		}	

		// Return $post in current lang pour tous les types de posts
		private function traduire_post( $post ){
			if(!$post)
				return false;


			switch ( $this -> set_locale_slug ) {
				case 'fr':
					# defaut : do nothing
					break;
				
				default:
					// Get connected wordings
					$connected = $this -> get_connected_wording($post);

					// si no connected : return post
					if(!$connected)
						return $post;

					// Loop les connected
					foreach ( $connected as $connected_post ) {

						// Return toutes les customs taxo du connected wording 
						// Parmi lesquelles il y a la taxo "langue"
					    $taxonomies = get_object_taxonomies($connected_post);
						// Loop 		
					    foreach ($taxonomies as $taxonomy){

					    	// On ne loop pas toutes les taxo, uniquement "langue" 
					    	$langue_taxo = 'langue';
					    	// Valeur de taxo "langue" pr ce connected wording
			    	        $terms = get_the_terms( $connected_post->ID, $langue_taxo );
			    	        // Loop les valeurs de taxo 
	                    	foreach ( $terms as $term ){
	                    		if( $term -> slug == $this -> set_locale_slug )
	                    			$post = $connected_post;
    	                    }
					    }
					}
					break;
			}			
			return $post;
		}	

		// Return un champ CPT wording connected au post ds la langue en cours   
		private function l_post($post, $field ){

			if(''==$field)
				$field = "post_title";
			

			if(!$post)
				return false;

			// Traduction
			$post = $this -> traduire_l_( $post );			
			// RETURN UN DES FIELDS DU POST
			switch ($field) {
				case 'post_title':
				case 'post_content':
					return $post -> $field;
					break;
			}
		}

		// Return les champs custom d'une cat dans la langue en cours
		private function l_category( $category , $field ){

			if(''==$field)
				$field = "name";
					
			if(!$category)
				return;

			$category = $this -> traduire_l_($category);

			switch ($field) {
				case 'name':
				case 'description':
					return $category -> $field;
					break;
			}
		}
		// traduit une categorie depuis ses options custom
		private function traduire_category($original){
			switch ( $this -> set_locale_slug ) {
				case 'fr':
					# defaut : do nothing
					break;

				default:
					// Get les options custom de la categorie
				    $tag_extra_fields = get_option(categories_i18n_options);
				    if($tag_extra_fields){
    				   	$tag_extra_fields = array_shift($tag_extra_fields);
    				   	// Return fields traduits depuis option dont le nom contient la langue courante
    					$original -> name 			= $tag_extra_fields [ $this -> set_locale_slug ];	
    					$original -> description 	= $tag_extra_fields [ $this -> set_locale_slug . "-description"];	
    				}
					break;
				}
			return $original;
		}

		// Return connected wording CPT
		private function get_connected_wording($post){

			// Nom de la connection p2p par defaut
			$connected_type = $post -> post_type . '_to_wording';

			// Nom de la connection p2p pour les post_tye = 'post'
			if ($post -> post_type == 'post') 
				$connected_type = 'posts_to_wording';
				
			// Get connected wordings
			$connected = get_posts( array(
			  'connected_type' 		=> $connected_type,
			  'connected_items' 	=> $post->ID,
			  'nopaging'			=> true,
			  'suppress_filters' 	=> false
			) );	

			// si no connected : return post
			if(sizeof($connected)<=0)
				return false;

			return $connected;
		}

		// Affiche un menu dans la langue courante
		public function get_menu($args){

		    // Test la langue courante
			switch ( $this -> set_locale_slug ) {
				case 'fr':
					# defaut : do nothing
					$slug =  $args['menu'];
					break;

				default:
					// return un slug pour retrouver le menu associé 
					// (ex : "Menu Primaire EN" est le menu traduit de "Menu Primaire")  
					$slug = $args['menu'] . ' ' . strtoupper($this -> set_locale_slug);
					break;
			}	
			// echo le menu
		    wp_nav_menu(array(
		        'menu' => $slug
		    ) );
		}


	} // endof Class

	// Call plugin dans global
	global $I18n;
	$I18n = new Deli_I18n_Plugin();	
}
?>