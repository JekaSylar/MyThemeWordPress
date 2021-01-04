<?php



##  отменим показ выбранного термина наверху в checkbox списке терминов
add_filter('wp_terms_checklist_args', 'set_checked_ontop_default', 10);
function set_checked_ontop_default($args)
{
	// изменим параметр по умолчанию на false
	if (!isset($args['checked_ontop']))
		$args['checked_ontop'] = false;

	return $args;
}

## Добавляем все типы записей в виджет "Прямо сейчас" в консоли
add_action('dashboard_glance_items', 'add_right_now_info');
function add_right_now_info($items)
{

	if (!current_user_can('edit_posts')) return $items; // выходим

	// типы записей
	$args = array('public' => true, '_builtin' => false);

	$post_types = get_post_types($args, 'object', 'and');

	foreach ($post_types as $post_type) {
		$num_posts = wp_count_posts($post_type->name);
		$num       = number_format_i18n($num_posts->publish);
		$text      = _n($post_type->labels->singular_name, $post_type->labels->name, intval($num_posts->publish));

		$items[] = "<a href=\"edit.php?post_type=$post_type->name\">$num $text</a>";
	}

	// таксономии
	$taxonomies = get_taxonomies($args, 'object', 'and');

	foreach ($taxonomies as $taxonomy) {
		$num_terms = wp_count_terms($taxonomy->name);
		$num       = number_format_i18n($num_terms);
		$text      = _n($taxonomy->labels->singular_name, $taxonomy->labels->name, intval($num_terms));

		$items[] = "<a href='edit-tags.php?taxonomy=$taxonomy->name'>$num $text</a>";
	}

	// пользователи
	global $wpdb;

	$num  = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->users");
	$text = _n('User', 'Users', $num);

	$items[] = "<a href='users.php'>$num $text</a>";

	return $items;
}




## заменим слово "запии" на "посты" для типа записей 'post'
/*add_filter('post_type_labels_post', 'rename_posts_labels');
function rename_posts_labels( $labels ){
	// заменять автоматически нельзя: Запись = Статья, а в тексте получим "Просмотреть статья"

	$new = array(
		'name'                  => 'Блог',
		'singular_name'         => 'Блог',
		'add_new'               => 'Добавить запись',
		'add_new_item'          => 'Добавить запись',
		'edit_item'             => 'Редактировать запись',
		'new_item'              => 'Новая запись',
		'view_item'             => 'Просмотреть запись',
		'search_items'          => 'Поиск',
		'not_found'             => 'Нечего не найденно',
		'not_found_in_trash'    => 'Поиск не дал результатов',
		'parent_item_colon'     => '',
		'all_items'             => 'Все записи',
		'archives'              => 'Архив',
		'insert_into_item'      => 'Вставить в запись',
		'uploaded_to_this_item' => 'Загруженные запись для этого',
		'featured_image'        => 'Изображение',
		'filter_items_list'     => 'Фильтр',
		'items_list_navigation' => 'Навигация',
		'items_list'            => 'Список запись',
		'menu_name'             => 'Блог',
		'name_admin_bar'        => 'Добавить запись', // пункте "добавить"
	);

	return (object) array_merge( (array) $labels, $new );
}
*/

add_action('admin_menu', 'add_user_menu_bubble');
function add_user_menu_bubble()
{
	global $menu;

	// записи
	$count = wp_count_posts()->pending; // на утверждении
	if ($count) {
		foreach ($menu as $key => $value) {
			if ($menu[$key][2] == 'edit.php') {
				$menu[$key][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . $count . '</span></span>';
				break;
			}
		}
	}
}






/*add_action('login_head', 'my_custom_login_logo');
function my_custom_login_logo(){
	echo '<style type="text/css">
	h1 a { background-image:url('.get_bloginfo('template_directory').'/img/logo-white.svg) !important; background-size: 180px !important; width: 200px !important; }
	</style>';
}*/

## Фильтр элементо втаксономии для метабокса таксономий в админке.
## Позволяет удобно фильтровать (искать) элементы таксономии по назанию, когда их очень много
add_action('admin_print_scripts', 'my_admin_term_filter', 99);
function my_admin_term_filter()
{
	$screen = get_current_screen();

	if ('post' !== $screen->base) return; // только для страницы редактирвоания любой записи
?>
	<script>
		jQuery(document).ready(function($) {
			var $categoryDivs = $('.categorydiv');

			$categoryDivs.prepend(
				'<input type="search" class="fc-search-field" placeholder="Поиск..." style="width:100%" />');

			$categoryDivs.on('keyup search', '.fc-search-field', function(event) {

				var searchTerm = event.target.value,
					$listItems = $(this).parent().find('.categorychecklist li');

				if ($.trim(searchTerm)) {
					$listItems.hide().filter(function() {
						return $(this).text().toLowerCase().indexOf(searchTerm.toLowerCase()) !== -1;
					}).show();
				} else {
					$listItems.show();
				}
			});
		});
	</script>
<?php
}



## Добавляет миниатюры записи в таблицу записей в админке
if (1) {
	add_action('init', 'add_post_thumbs_in_post_list_table', 20);
	function add_post_thumbs_in_post_list_table()
	{
		// проверим какие записи поддерживают миниатюры
		$supports = get_theme_support('post-thumbnails');

		// $ptype_names = array('post','page'); // указывает типы для которых нужна колонка отдельно

		// Определяем типы записей автоматически
		if (!isset($ptype_names)) {
			if ($supports === true) {
				$ptype_names = get_post_types(array('public' => true), 'names');
				$ptype_names = array_diff($ptype_names, array('attachment'));
			}
			// для отдельных типов записей
			elseif (is_array($supports)) {
				$ptype_names = $supports[0];
			}
		}

		// добавляем фильтры для всех найденных типов записей
		foreach ($ptype_names as $ptype) {
			add_filter("manage_{$ptype}_posts_columns", 'add_thumb_column');
			add_action("manage_{$ptype}_posts_custom_column", 'add_thumb_value', 10, 2);
		}
	}

	// добавим колонку
	function add_thumb_column($columns)
	{
		// подправим ширину колонки через css
		add_action('admin_notices', function () {
			echo '
			<style>
				.column-thumbnail{ width:80px; text-align:center; }
			</style>';
		});

		$num = 1; // после какой по счету колонки вставлять новые

		$new_columns = array('thumbnail' => __('Thumbnail'));

		return array_slice($columns, 0, $num) + $new_columns + array_slice($columns, $num);
	}

	// заполним колонку
	function add_thumb_value($colname, $post_id)
	{
		if ('thumbnail' == $colname) {
			$width  = $height = 45;

			// миниатюра
			if ($thumbnail_id = get_post_meta($post_id, '_thumbnail_id', true)) {
				$thumb = wp_get_attachment_image($thumbnail_id, array($width, $height), true);
			}
			// из галереи...
			elseif ($attachments = get_children(array(
				'post_parent'    => $post_id,
				'post_mime_type' => 'image',
				'post_type'      => 'attachment',
				'numberposts'    => 1,
				'order'          => 'DESC',
			))) {
				$attach = array_shift($attachments);
				$thumb = wp_get_attachment_image($attach->ID, array($width, $height), true);
			}

			echo empty($thumb) ? ' ' : $thumb;
		}
	}
}

add_filter('login_headerurl', 'custom_login_header_url');
function custom_login_header_url($url)
{

	return home_url();
}


/**
 * Заполняет поле для атрибута alt на основе заголовка изображения при его вставки в контент поста.
 *
 * @param array $response
 *
 * @return array
 */
function change_empty_alt_to_title($response)
{
	if (!$response['alt']) {
		$response['alt'] = sanitize_text_field($response['title']);
	}

	return $response;
}

add_filter('wp_prepare_attachment_for_js', 'change_empty_alt_to_title');


// удаляет H2 из шаблона пагинации
add_filter('navigation_markup_template', 'my_navigation_template', 10, 2);
function my_navigation_template($template, $class)
{
	/*
	Вид базового шаблона:
	<nav class="navigation %1$s" role="navigation">
		<h2 class="screen-reader-text">%2$s</h2>
		<div class="nav-links">%3$s</div>
	</nav>
	*/

	return '
	<nav class="navigation %1$s" role="navigation">
		<div class="nav-links">%3$s</div>
	</nav>    
	';
}

function true_rewrite_search_results_permalink()
{
	global $wp_rewrite;
	// обязательно проверим, включены ли чпу, чтобы не закосячить весь поиск
	if (!isset($wp_rewrite) || !is_object($wp_rewrite) || !$wp_rewrite->using_permalinks())
		return;
	if (is_search() && !is_admin() && strpos($_SERVER['REQUEST_URI'], "/search/") === false && !empty($_GET['s'])) {
		wp_redirect(site_url() . "/search/" . urlencode(get_query_var('s')));
		exit;
	}
}

add_action('template_redirect', 'true_rewrite_search_results_permalink');

// вторая функция нужна для поддержки русских букв и специальных символов
function true_urldecode_s($query)
{
	if (is_search()) {
		$query->query_vars['s'] = urldecode($query->query_vars['s']);
	}
	return $query;
}

add_filter('parse_query', 'true_urldecode_s');

add_filter('body_class', function ($classes) {
	foreach ($classes as $key => $class) {
		if ($class == "page") {
			unset($classes[$key]);
		}
	}
	return $classes;
}, 1000);

//Сортировка дереом таксономию https://wp-kama.ru/question/nuzhno-poluchit-spisok-kategorij-taksonomii-soblyudaya-ierarhiyu
function sort_terms_hierarchicaly( & $cats, & $into, $parentId = 0 ){
	foreach( $cats as $i => $cat ){
		if( $cat->parent == $parentId ){
			$into[ $cat->term_id ] = $cat;
			unset( $cats[$i] );
		}
	}

	foreach( $into as $top_cat ){
		$top_cat->children = array();
		sort_terms_hierarchicaly( $cats, $top_cat->children, $top_cat->term_id );
	}
}

add_filter('style_loader_tag', 'myplugin_remove_type_attr', 10, 2);
add_filter('script_loader_tag', 'myplugin_remove_type_attr', 10, 2);

function myplugin_remove_type_attr($tag, $handle) {
    return preg_replace( "/type=['\"]text\/(javascript|css)['\"]/", '', $tag );
}

/*
//исключение страниц из результатов поиска start
function wph_exclude_pages($query) {
	if ($query->is_search) {
		 $query->set('post_type', 'post');
		 $query->set('post_type', 'page');
		 $query->set('post_type', 'materials');
		 $query->set('post_type', 'faq');
		 $query->set('post_type', 'reviews');
		 $query->set('post_type', 'reviews');
	}
	return $query;
}
add_filter('pre_get_posts','wph_exclude_pages');
//исключение страниц из результатов поиска end*/