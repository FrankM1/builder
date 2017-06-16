# PHP Hooks

## TOC
  * [Frontend Filters](#frontend-filters)
    + [`builder/frontend/the_content`](#builderfrontendthe_content)
    + [`builder/widget/render_content`](#builderwidgetrender_content)
    + [`builder/frontend/print_google_fonts`](#builderfrontendprint_google_fonts)
  * [Editor Filters](#editor-filters)
    + [`builder/element/print_template`](#builderelementprint_template)
  * [Init Actions](#init-actions)
    + [`builder/loaded`](#builderloaded)
    + [`builder/init`](#builderinit)
    + [`builder/widget/{name}/skins_init`](#builderwidgetnameskins_init)
  * [Frontend Actions](#frontend-actions)
    + [`builder/frontend/before_enqueue_scripts`](#builderfrontendbefore_enqueue_scripts)
    + [`builder/frontend/after_register_styles`](#builderfrontendafter_register_styles)
    + [`builder/element/parse_css`](#builderelementparse_css)
    + [`builder/frontend/{section|column|widget}/before_render`](#builderfrontendsectioncolumnwidgetbefore_render)
    + [`builder/frontend/{section|column|widget}/after_render`](#builderfrontendsectioncolumnwidgetafter_render)
    + [`builder/widgets/widgets_registered`](#builderwidgetswidgets_registered)
  * [Editor Actions](#editor-actions)
    + [`builder/editor/after_save`](#buildereditorafter_save)
    + [`builder/editor/before_enqueue_scripts`](#buildereditorbefore_enqueue_scripts)
    + [`builder/element/before_section_start`](#builderelementbefore_section_start)
    + [`builder/element/after_section_end`](#builderelementafter_section_end)
    + [`builder/element/{$element_name}/{$section_id}/before_section_start`](#builderelementelementnamesection_idbefore_section_start)
    + [`builder/element/{element_name}/{section_id}/after_section_end`](#builderelementelementnamesection_idafter_section_end)
    + [`builder/element/after_section_start`](#builderelementafter_section_start)
    + [`builder/element/before_section_end`](#builderelementbefore_section_end)
    + [`builder/element/{$element_name}/{$section_id}/after_section_start`](#builderelementelementnamesection_idafter_section_start)
    + [`builder/element/{element_name}/{section_id}/before_section_end`](#builderelementelementnamesection_idbefore_section_end)
  * [Preview Actions](#preview-actions)
    + [`builder/preview/enqueue_styles`](#builderpreviewenqueue_styles)

## Frontend Filters

### `builder/frontend/the_content`
Applied to frontend HTML content (the entire Builder content in page).

#### Arguments

Argument          | Type         | Description
------------      | :------:     | ---------------------------------------------
`content`         | *`string`*   | The entire Builder HTML output of current page/post
 
#### Example

```php
add_action( 'builder/frontend/the_content', function( $content ) {
	if ( ! membership_plugin_is_allowed_content() ) {
		$content = __( 'Forbidden', 'membership_plugin' );
	}
	
	return $content;
} );
```

### `builder/widget/render_content`
Applied to the PHP html content of a single widget. ( in the Editor it will be shown after the finish edit the element. to change the JavaScript Template see [`builder/element/print_template`](#builderelementprint_template))
 
#### Arguments

Argument          | Type              | Description
------------      | :------:          | ----------------------
`content`         | *`string`*        | The widget HTML output
`widget`          | *`Widget_Base`*   | The widget instance
 
#### Example

 ```php
add_action( 'builder/widget/render_content', function( $content, $widget ) {
	if ( 'heading' === $widget->get_name() ) {
		$settings = $widget->get_settings();
	
		if ( ! empty( $settings['link']['is_external'] ) ) {
			$content .= '<i class="fa fa-external-link" aria-hidden="true"></i>';
		}
	}
	
	return $content;
}, 10, 2 );
 ```
 
 ### `builder/frontend/print_google_fonts`
 Used to prevent loading of Google Fonts by Builder
 
 #### Arguments
 None
  
 #### Example
 
  ```php
add_filter( 'builder/frontend/print_google_fonts', '__return_false' );
 ```

## Editor Filters

### `builder/element/print_template`
Applied to the javascript preview templates.

#### Arguments

Argument          | Type              | Description
------------      | :------:          | ----------------------
`template`        | *`string`*        | The JavaScript template output
`widget`          | *`Widget_Base`*   | The widget instance
 
#### Example

 ```php
add_action( 'builder/element/print_template', function( $template, $widget ) {
	if ( 'heading' === $widget->get_name() ) {
		$old_template = '<a href="\' + settings.link.url + \'">\' + title_html + \'</a>';
		$new_template = '<a href="\' + settings.link.url + \'">\' + title_html + ( settings.link.is_external ? \'<i class="fa fa-external-link" aria-hidden="true"></i>\' : \'\' ) + \'</a>';
		$template = str_replace( $old_template, $new_template, $template );
	}

	return $template;
}, 10, 2 );
 ```
 Note: The code above it for example only, we do not recommend to use `str_replace` on templates, because the template may be changed and the `str_replace` will fail. instead, take the whole original template and change it for your needs.
 
## Init Actions

### `builder/loaded`
Builder plugin is loaded, before load all components

#### Arguments
None
 
#### Example

 ```php
add_action( 'builder/loaded', 'load_my_plugin' );
 ```

### `builder/init`
Builder is fully loaded

#### Arguments
None
 
#### Example

 ```php
// Add a custom category for panel widgets
add_action( 'builder/init', function() {
	\Builder\Plugin::$instance->elements_manager->add_category( 
		'theme-elements',
		[
			'title' => __( 'Theme Elements', 'theme-domain' ),
			'icon' => 'fa fa-plug', //default icon
		],
		2 // position
	);
} );
```

### `builder/widget/{name}/skins_init`
Runs after widget construction. 
Here is th place to register custom skins. 

#### Arguments

Argument          | Type              | Description
------------      | :------:          | ----------------------
`widget`          | *`Widget_Base`*   | The widget instance
 
#### Example

 ```php
// Add a custom skin for the Google Maps widget
add_action( 'builder/widget/google_maps/skins_init', function( $widget ) {
	$widget->add_skin( new MySkins\Skin_Dark_Map( $widget ) );
} );
```

## Frontend Actions

### `builder/frontend/before_enqueue_scripts`
Before the frontend scripts enqueuing.

#### Arguments
None
 
#### Example

 ```php
add_action( 'builder/frontend/before_enqueue_scripts', function() {
	wp_enqueue_script(
		'plugin-name-frontend',
		'plugin-url/assets/frontend.js',
		[
			'builder-frontend', // dependency
		],
		'plugin_version',
		true // in_footer
	);
} );
```

### `builder/frontend/after_register_styles`
After Builder registers all styles.

#### Arguments
None
 
#### Example

 ```php
add_action( 'builder/frontend/after_register_styles', function() {
    wp_dequeue_style( 'font-awesome' );
} );
```

### `builder/element/parse_css`
After Parse the element CSS in order to generate the CSS file

#### Arguments
Argument          | Type              | Description
------------      | :------:          | ----------------------
`post_css`        | *`Post_CSS_File`* | The Post CSS generator
`element`         | *`Element_Base`*  | The element instance
 
#### Example

 ```php
add_action(	'builder/element/parse_css', function( $post_css, $element ) {
	$item_width = some_get_theme_config_function( 'item_width' );
	/**
	 * @var \Builder\Post_CSS_File $post_css
	 * @var \Builder\Element_Base  $element
	 */
	$post_css->get_stylesheet()->add_rules( $element->get_unique_selector(), [
		'width' => $item_width . 'px',
	] );
}, 10, 2 );
```

### `builder/frontend/{element|widget}/before_render`
### `builder/frontend/{element|widget}/after_render`
Before/after the element is printed

#### Arguments

Argument          | Type              | Description
------------      | :------:          | ----------------------
`element`         | *`Element_Base`*  | The element instance
 
#### Example

```php
add_action( 'builder/frontend/element/before_render', function ( \Builder\Element_Base $element ) {
	if ( ! $element->get_settings( 'my-custom-settings' ) ) {
		return;
	}

	$element->add_render_attribute( '_wrapper', [
		'class' => 'my-custom-class',
		'data-my_data' => 'my-data-value',
	] );
} );
```

### `builder/widgets/widgets_registered`
The place to register your custom widgets. 

#### Arguments

Argument          | Type               | Description
------------      | :------:           | ----------------------
`widgets_manager` | *`Widgets_Manager`*| The widgets manager instance

#### Example

```php
add_action( 'builder/widgets/widgets_registered', function( $widgets_manager ) {
	require 'plugin-path/widgets/my-widget.php';
    
    $widgets_manager->register_widget_type( new My_Widget() );
} );
```

## Editor Actions
### `builder/editor/after_save`
Runs after saving Builder data.

#### Arguments

Argument          | Type              | Description
------------      | :------:          | ----------------------
`post_id`         | *`integer`*       | The post ID
`editor_data`     | *`array`*         | Array of Builder elements

#### Example

```php
add_action( 'builder/editor/after_save', function( $post_id, $editor_data ) {
    // Activity Log Plugin
    aal_insert_log(
		[
			'action' => 'saved',
			'object_type' => 'Builder Data',
			'object_id' => $post_id,
			'object_name' => get_the_title( $post_id ),
		]
	);
}
```

### `builder/editor/before_enqueue_scripts`
Before the editor scripts enqueuing.

#### Arguments
None
 
#### Example

 ```php
add_action( 'builder/editor/before_enqueue_scripts', function() {
	wp_enqueue_script(
		'plugin-name-editor',
		'plugin-url/assets/editor.js',
		[
			'builder-editor', // dependency
		],
		'plugin_version',
		true // in_footer
	);
} );
```

start_controls_section
### `builder/element/before_section_start`
### `builder/element/after_section_end`
Runs before/after an editor section is registered.
Here is the place to add additional sections before and after each section for all elements in panel
If you need to add a section in a specific place ( a specific element & section ), prefer to use the [next hook](#builderelementelement_namesection_idbefore_section_start)

#### Arguments

Argument          | Type              | Description
------------      | :------:          | ----------------------
`element`         | *`Element_Base`*  | The edited element.
`section_id`      | *`string`*        | Current section  id
`args`            | *`array`*         | The $args that sent to `$element->start_controls_section`
 
#### Example

 ```php

add_action( 'builder/element/before_section_start', function( $element, $section_id, $args ) {
	/** @var \Builder\Element_Base $element */
	if ( 'section' === $element->get_name() && 'section_background' === $section_id ) {

		$element->start_controls_section(
			'custom_section',
			[
				'tab' => \Builder\Controls_Manager::TAB_STYLE,
				'label' => __( 'Custom Section', 'plugin-name' ),
			]
		);

		$element->add_control(
			'custom_control',
			[
			'type' => \Builder\Controls_Manager::NUMBER,
			'label' => __( 'Custom Control', 'plugin-name' ),
			]
		);

		$element->end_controls_section();
	}
}, 10, 3 );
```

### `builder/element/{$element_name}/{$section_id}/before_section_start`
### `builder/element/{element_name}/{section_id}/after_section_end`
Runs before/after a specific element ( like `heading`) and a specific section ( like `section_title` )

#### Arguments

Argument          | Type              | Description
------------      | :------:          | ----------------------
`element`         | *`Element_Base`*  | The edited element.
`args`            | *`array`*         | The $args that sent to `$element->start_controls_section`
 
#### Example

```php
add_action( 'builder/element/heading/section_title/before_section_start', function( $element, $args ) {
	/** @var \Builder\Element_Base $element */
	$element->start_controls_section(
		'custom_section',
		[
			'tab' => \Builder\Controls_Manager::TAB_STYLE,
			'label' => __( 'Custom Section', 'plugin-name' ),
		]
	);

	$element->add_control(
		'custom_control',
		[
			'type' => \Builder\Controls_Manager::NUMBER,
			'label' => __( 'Custom Control', 'plugin-name' ),
		]
	);

	$element->end_controls_section();
}, 10, 2 );
```

### `builder/element/after_section_start`
### `builder/element/before_section_end`
Runs within an editor section. after it was opened / before the section is closed.
Here is the place to add additional controls to existing sections.
If you need to add a control to a specific place ( a specific element & section ), prefer to use the [next hook](#builderelementelement_namesection_idafter_section_start)

#### Arguments

Argument          | Type              | Description
------------      | :------:          | ----------------------
`element`         | *`Element_Base`*  | The edited element.
`section_id`      | *`string`*        | Current section id
`args`            | *`array`*         | The $args that sent to `$element->start_controls_section`
 
#### Example

```php
add_action( 'builder/element/after_section_start', function( $element, $section_id, $args ) {
	/** @var \Builder\Element_Base $element */
	if ( 'section' === $element->get_name() && 'section_background' === $section_id ) {
		$element->add_control(
			'custom_control',
			[
				'type' => \Builder\Controls_Manager::NUMBER,
				'label' => __( 'Custom Control', 'plugin-name' ),
			]
		);
	}
}, 10, 3 );
```

### `builder/element/{$element_name}/{$section_id}/after_section_start`
### `builder/element/{element_name}/{section_id}/before_section_end`

Runs within an editor section. after it was opened / before the section is closed.
Here is the place to add additional controls before and after a specific element ( like `heading`) and a specific section ( like `section_title` )

#### Arguments

Argument          | Type              | Description
------------      | :------:          | ----------------------
`element`         | *`Element_Base`*  | The edited element.
`args`            | *`array`*         | The $args that sent to `$element->start_controls_section`
 
#### Example

```php
add_action( 'builder/element/heading/section_title/before_section_start', function( $element, $args ) {
	/** @var \Builder\Element_Base $element */
	$element->add_control(
		'custom_control',
		[
			'type' => \Builder\Controls_Manager::NUMBER,
			'label' => __( 'Custom Control', 'plugin-name' ),
		]
	);
}, 10, 2 );
```

## Preview Actions
### `builder/preview/enqueue_styles`
Before the preview styles enqueuing.

#### Arguments
None
 
#### Example

 ```php
add_action( 'builder/preview/enqueue_styles', function() {
	wp_enqueue_style(
		'builder-preview-style',
		url/to/style.css',
		[],
		'plugin-version'
	);
} );
```