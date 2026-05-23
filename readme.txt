=== Dynamic Fields Pro ===
Contributors: dynamicfieldspro
Tags: custom fields, meta box, field groups, ACF alternative, repeater, gallery, wysiwyg, faq
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete custom fields solution for WordPress. Create field groups, assign them to posts/pages/CPTs with flexible location rules, and access field data with a clean template-tag API.

== Description ==

Dynamic Fields Pro is a full-featured custom fields plugin for WordPress. It provides:

* **22 field types**: Text, Textarea, Number, Email, URL, Password, Select, Checkbox, Radio, True/False, Post Object, Relationship, Taxonomy, User, Date Picker, Color Picker, Image, Gallery, File, WYSIWYG Editor, Repeater
* **Visual field builder** with drag-to-reorder
* **Location rules** — show field groups based on post type, template, taxonomy, user role, page parent, and more
* **Repeater fields** with collapse/expand, drag-to-sort rows, nested repeaters
* **Gallery field** — multi-image picker with thumbnail grid and min/max limits
* **File field** — any file type upload with size display and allowed mime types filter
* **WYSIWYG Editor field** — full TinyMCE editor per field
* **REST API** endpoints: `GET /wp-json/dfp/v1/fields/{post_id}` and `POST /wp-json/dfp/v1/fields/{post_id}`
* **Import / Export** field groups as JSON
* **Template tag API** compatible with ACF patterns (with `function_exists()` guards)

== Installation ==

1. Upload the `dynamic-fields-pro` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** screen in WordPress Admin
3. Navigate to **Dynamic Fields** in the admin menu
4. Click **Add New** to create your first field group

== Creating Your First Field Group ==

1. Go to **Dynamic Fields → Add New**
2. Enter a **Group Title** (e.g. "Landing Page Fields")
3. Click **+ Add Field** to add fields
4. Set each field's Label, Name, and Type
5. Click the **Location Rules** tab — set where the group appears (e.g. Post Type = Page)
6. Click **Save Field Group**

The fields will now appear as a meta box on the edit screen of the assigned posts/pages.

== All Field Types ==

= Basic =
* **Text** — Short strings, headings, labels
* **Textarea** — Multi-line plain text
* **Number** — Prices, counts, ratings
* **Email** — Email addresses
* **URL** — Links, button URLs
* **Password** — Stored hashed

= Content =
* **Image** — Single image from media library (returns array with url, alt, width, height, sizes)
* **Gallery** — Multiple images, thumbnail grid, min/max limits, return format: array/url/id
* **File** — Any file upload, allowed mime types filter, return format: array/url/id
* **WYSIWYG Editor** — Full TinyMCE editor with toolbar and media upload options

= Choice =
* **Select** — Dropdown, return as value or array {value, label}
* **Checkbox** — Multiple choices, returns array
* **Radio** — Single choice
* **True / False** — Toggle switch, returns 1 or 0

= Relational =
* **Post Object** — Returns WP_Post object
* **Relationship** — Multiple posts, returns WP_Post[]
* **Taxonomy** — Categories/tags, returns WP_Term[]
* **User** — Returns WP_User object

= jQuery =
* **Date Picker** — Stored as Ymd (20260101), configurable display format
* **Color Picker** — Returns hex value (#ffffff)

= Layout =
* **Repeater** — Repeating row sets with sub-fields, supports nested repeaters

== Template Tag API ==

= get_field() =

Retrieve the value of a custom field.

    $value = get_field( 'hero_title' );
    $value = get_field( 'hero_title', $post_id ); // specific post

= the_field() =

Echo the value (escaped with wp_kses_post).

    the_field( 'hero_title' );

= get_fields() =

All fields for a post as an associative array.

    $fields = get_fields();
    foreach ( $fields as $name => $value ) {
        echo $name . ': ' . $value;
    }

= update_field() =

Programmatically update a field value.

    update_field( 'hero_title', 'New Value', $post_id );

= delete_field() =

Delete a field value.

    delete_field( 'hero_title', $post_id );

= Repeater fields =

    if ( have_rows( 'team_members' ) ) {
        while ( have_rows( 'team_members' ) ) {
            the_row();
            $name  = get_sub_field( 'name' );
            $role  = get_sub_field( 'role' );
            $photo = get_sub_field( 'photo' ); // returns image array
            echo '<p>' . esc_html( $name ) . ' — ' . esc_html( $role ) . '</p>';
        }
    }

= Nested repeaters =

    while ( have_rows( 'sections' ) ) {
        the_row();
        echo '<h2>' . esc_html( get_sub_field( 'section_title' ) ) . '</h2>';
        while ( have_rows( 'items' ) ) {
            the_row();
            echo '<p>' . esc_html( get_sub_field( 'item_text' ) ) . '</p>';
        }
    }

= get_row() and get_row_index() =

    while ( have_rows( 'slides' ) ) {
        the_row();
        $index = get_row_index(); // 0-based
        $row   = get_row();       // full row as associative array
        echo 'Slide ' . ( $index + 1 ) . ': ' . $row['slide_title'];
    }

= reset_rows() =

Reset the loop pointer so you can iterate again.

    reset_rows( 'team_members', $post_id );

= Image field =

    $image = get_field( 'hero_image' );
    if ( $image ) {
        echo '<img src="' . esc_url( $image['url'] ) . '"
                   width="' . absint( $image['width'] ) . '"
                   height="' . absint( $image['height'] ) . '"
                   alt="' . esc_attr( $image['alt'] ) . '">';
    }

= Gallery field =

    $images = get_field( 'project_gallery' ); // array of image arrays
    if ( $images ) {
        foreach ( $images as $image ) {
            echo '<img src="' . esc_url( $image['sizes']['medium'] ) . '"
                       alt="' . esc_attr( $image['alt'] ) . '">';
        }
    }

= File field =

    $file = get_field( 'brochure_pdf' );
    if ( $file ) {
        echo '<a href="' . esc_url( $file['url'] ) . '" download>'
           . esc_html( $file['filename'] ) . '</a>';
        // $file keys: id, url, title, filename, filesize, mime_type
    }

= WYSIWYG field =

    $content = get_field( 'page_content' );
    if ( $content ) {
        echo wp_kses_post( $content );
    }
    // or simply:
    the_field( 'page_content' );

= Post Object field =

    $related_post = get_field( 'related_post' ); // WP_Post object
    if ( $related_post ) {
        echo '<a href="' . esc_url( get_permalink( $related_post ) ) . '">'
           . esc_html( $related_post->post_title ) . '</a>';
    }

= Taxonomy field =

    $terms = get_field( 'featured_categories' ); // array of WP_Term objects
    if ( $terms ) {
        foreach ( $terms as $term ) {
            echo '<a href="' . esc_url( get_term_link( $term ) ) . '">'
               . esc_html( $term->name ) . '</a>';
        }
    }

= User field =

    $author = get_field( 'article_author' ); // WP_User object
    if ( $author ) {
        echo esc_html( $author->display_name );
    }

= True / False field =

    if ( get_field( 'show_sidebar' ) ) {
        get_sidebar();
    }

= Select / Radio field with Return Format = array =

    $color = get_field( 'brand_color' ); // { value, label }
    echo $color['label']; // "Midnight Blue"
    echo $color['value']; // "midnight_blue"

= Date Picker field =

    $raw  = get_field( 'event_date' ); // "20260101"
    $date = $raw ? DateTime::createFromFormat( 'Ymd', $raw ) : null;
    if ( $date ) {
        echo $date->format( 'F j, Y' ); // January 1, 2026
    }

= Color Picker as CSS variable =

    $color = get_field( 'brand_color' );
    if ( $color ) {
        echo '<style>:root { --brand-color: ' . esc_attr( $color ) . '; }</style>';
    }

== Location Rules ==

Location rules control which posts/pages show a field group.

Available rule types:
* Post Type — page, post, product, any CPT
* Page Template — e.g. templates/landing.php
* Post — specific post by title
* Taxonomy Term — any registered taxonomy term
* User Role — administrator, editor, author, etc.
* Page Parent — specific parent page ID
* Post Format — video, gallery, aside, etc.
* Post Status — publish, draft, pending, etc.
* Current User — logged in user
* Current User Role — subscriber, contributor, etc.
* Attachment MIME Type — image/png, application/pdf, etc.

Rules within a group = AND (all must match).
Multiple groups = OR (any group can match).

Example — show on Page AND landing template:

    Group 1:  Post Type == page  AND  Page Template == templates/landing.php

Example — show on Page OR Product:

    Group 1:  Post Type == page
    Group 2:  Post Type == product

== Landing Page Integration ==

Step 1: Create a field group called "Landing Page" with these fields:

    hero_title       → Text
    hero_subtitle    → Textarea
    hero_btn_text    → Text
    hero_btn_url     → URL
    hero_bg_image    → Image
    show_cta         → True / False
    cta_heading      → Text
    cta_btn_text     → Text
    cta_btn_url      → URL
    features         → Repeater
      ├ icon         → Image  (sub-field)
      ├ title        → Text   (sub-field)
      └ description  → Textarea (sub-field)

Set Location to: Post Type == page, Page Template == templates/landing-page.php

Step 2: Create templates/landing-page.php in your theme:

    <?php /* Template Name: Landing Page */ get_header(); ?>

    <?php
    $hero_title = get_field( 'hero_title' );
    $hero_bg    = get_field( 'hero_bg_image' );
    ?>
    <section class="hero"
        <?php if ( $hero_bg ) echo 'style="background-image:url(' . esc_url( $hero_bg['url'] ) . ')"'; ?>>
        <h1><?php echo esc_html( $hero_title ); ?></h1>
        <?php the_field( 'hero_subtitle' ); ?>
        <a href="<?php the_field( 'hero_btn_url' ); ?>" class="btn">
            <?php the_field( 'hero_btn_text' ); ?>
        </a>
    </section>

    <?php if ( have_rows( 'features' ) ) : ?>
    <section class="features">
        <?php while ( have_rows( 'features' ) ) : the_row();
            $icon = get_sub_field( 'icon' ); ?>
        <div class="feature-card">
            <?php if ( $icon ) : ?>
                <img src="<?php echo esc_url( $icon['url'] ); ?>" alt="<?php echo esc_attr( $icon['alt'] ); ?>">
            <?php endif; ?>
            <h3><?php echo esc_html( get_sub_field( 'title' ) ); ?></h3>
            <p><?php echo esc_html( get_sub_field( 'description' ) ); ?></p>
        </div>
        <?php endwhile; ?>
    </section>
    <?php endif; ?>

    <?php if ( get_field( 'show_cta' ) ) : ?>
    <section class="cta">
        <h2><?php the_field( 'cta_heading' ); ?></h2>
        <a href="<?php the_field( 'cta_btn_url' ); ?>" class="btn"><?php the_field( 'cta_btn_text' ); ?></a>
    </section>
    <?php endif; ?>

    <?php get_footer(); ?>

== Dynamic FAQ ==

Create a field group with:

    faq_items     → Repeater
      ├ question  → Text       (sub-field)
      └ answer    → WYSIWYG    (sub-field)

Template:

    <?php if ( have_rows( 'faq_items' ) ) : ?>
    <section class="faq">
        <?php while ( have_rows( 'faq_items' ) ) : the_row(); ?>
        <div class="faq-item">
            <button class="faq-q" aria-expanded="false">
                <?php echo esc_html( get_sub_field( 'question' ) ); ?>
            </button>
            <div class="faq-a" hidden>
                <?php echo wp_kses_post( get_sub_field( 'answer' ) ); ?>
            </div>
        </div>
        <?php endwhile; ?>
    </section>
    <script>
    document.querySelectorAll('.faq-q').forEach(function(btn){
        btn.addEventListener('click',function(){
            var open = this.getAttribute('aria-expanded')==='true';
            this.setAttribute('aria-expanded',!open);
            this.nextElementSibling.hidden = open;
        });
    });
    </script>
    <?php endif; ?>

== Team Members Repeater ==

    team_members   → Repeater
      ├ photo      → Image
      ├ name       → Text
      ├ role       → Text
      ├ bio        → Textarea
      └ linkedin   → URL

    <?php if ( have_rows( 'team_members' ) ) : ?>
    <div class="team-grid">
        <?php while ( have_rows( 'team_members' ) ) : the_row();
            $photo = get_sub_field( 'photo' ); ?>
        <div class="team-card">
            <?php if ( $photo ) : ?>
                <img src="<?php echo esc_url( $photo['sizes']['medium'] ); ?>" alt="">
            <?php endif; ?>
            <h3><?php echo esc_html( get_sub_field( 'name' ) ); ?></h3>
            <p class="role"><?php echo esc_html( get_sub_field( 'role' ) ); ?></p>
            <p><?php echo esc_html( get_sub_field( 'bio' ) ); ?></p>
            <?php $li = get_sub_field( 'linkedin' ); ?>
            <?php if ( $li ) : ?><a href="<?php echo esc_url( $li ); ?>">LinkedIn</a><?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

== Testimonials Repeater ==

    testimonials     → Repeater
      ├ quote        → Textarea
      ├ author_name  → Text
      ├ author_title → Text
      ├ author_photo → Image
      └ rating       → Select (1/2/3/4/5)

    <?php if ( have_rows( 'testimonials' ) ) : ?>
    <div class="testimonials">
        <?php while ( have_rows( 'testimonials' ) ) : the_row();
            $photo  = get_sub_field( 'author_photo' );
            $rating = (int) get_sub_field( 'rating' ); ?>
        <div class="testimonial">
            <div class="stars"><?php echo str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ); ?></div>
            <blockquote><?php echo esc_html( get_sub_field( 'quote' ) ); ?></blockquote>
            <?php if ( $photo ) : ?>
                <img src="<?php echo esc_url( $photo['sizes']['thumbnail'] ); ?>" alt="">
            <?php endif; ?>
            <strong><?php echo esc_html( get_sub_field( 'author_name' ) ); ?></strong>
            <span><?php echo esc_html( get_sub_field( 'author_title' ) ); ?></span>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

== Pricing Plans Repeater ==

    pricing_plans  → Repeater
      ├ plan_name  → Text
      ├ price      → Text
      ├ period     → Select
      ├ features   → Textarea  (one feature per line)
      ├ btn_text   → Text
      ├ btn_url    → URL
      └ is_popular → True / False

    <?php if ( have_rows( 'pricing_plans' ) ) : ?>
    <div class="pricing-grid">
        <?php while ( have_rows( 'pricing_plans' ) ) : the_row();
            $popular  = get_sub_field( 'is_popular' );
            $features = array_filter( array_map( 'trim',
                            explode( "\n", get_sub_field( 'features' ) ) ) ); ?>
        <div class="plan<?php echo $popular ? ' popular' : ''; ?>">
            <?php if ( $popular ) echo '<span class="badge">Most Popular</span>'; ?>
            <h3><?php echo esc_html( get_sub_field( 'plan_name' ) ); ?></h3>
            <div class="price">
                <?php echo esc_html( get_sub_field( 'price' ) ); ?>
                <span><?php echo esc_html( get_sub_field( 'period' ) ); ?></span>
            </div>
            <ul><?php foreach ( $features as $f ) echo '<li>' . esc_html( $f ) . '</li>'; ?></ul>
            <a href="<?php echo esc_url( get_sub_field( 'btn_url' ) ); ?>" class="btn">
                <?php echo esc_html( get_sub_field( 'btn_text' ) ); ?>
            </a>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

== REST API ==

= GET /wp-json/dfp/v1/fields/{post_id} =

Returns all field values for a post.

Response:

    {
        "post_id": 42,
        "post_type": "page",
        "fields": {
            "hero_title": "Welcome",
            "hero_bg_image": { "id": 7, "url": "https://…", "alt": "" },
            "features": [
                { "title": "Fast", "description": "Very fast" }
            ]
        }
    }

Public posts are readable without authentication. Private posts require a logged-in user.

= POST /wp-json/dfp/v1/fields/{post_id} =

Update one or more field values. Requires edit_post capability.

Request body (JSON):

    {
        "hero_title": "New Headline",
        "show_cta": 1,
        "hero_bg_image": 7
    }

Response:

    {
        "success": true,
        "post_id": 42,
        "updated_fields": ["hero_title", "show_cta", "hero_bg_image"]
    }

JavaScript example:

    fetch('/wp-json/dfp/v1/fields/42', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpApiSettings.nonce
        },
        body: JSON.stringify({ hero_title: 'Updated' })
    }).then(res => res.json()).then(console.log);

== AJAX Endpoints ==

All AJAX endpoints require a valid nonce (dfpSettings.nonce) and manage_options capability.

* dfp_get_rule_values   — returns available values for a location rule param
* dfp_save_field_group  — save/update a field group
* dfp_toggle_group      — toggle a group active/inactive
* dfp_duplicate_group   — duplicate a group
* dfp_delete_group      — delete a group
* dfp_export_group      — download group JSON
* dfp_import_group      — import group from JSON

== Registering Custom Field Types ==

    add_action( 'dfp/register_fields', function( $dfp_fields ) {

        class My_Star_Rating_Field extends DFP_Field_Base {

            public function get_type()    { return 'star_rating'; }
            public function get_label()   { return 'Star Rating'; }
            public function get_defaults(){ return [ 'max' => 5 ]; }

            public function render_field( $field, $value ) {
                $max = isset( $field['max'] ) ? absint( $field['max'] ) : 5;
                for ( $i = 1; $i <= $max; $i++ ) {
                    echo '<label>';
                    echo '<input type="radio" name="' . esc_attr( $field['key'] ) . '" value="' . $i . '"'
                       . checked( absint( $value ), $i, false ) . '>';
                    echo ( $i <= absint( $value ) ? '★' : '☆' );
                    echo '</label>';
                }
            }

            public function render_field_settings( $field ) {
                $this->row( 'Max Stars',
                    '<input type="number" min="1" max="10" name="' . $field['key'] . '[max]"
                        value="' . esc_attr( isset( $field['max'] ) ? $field['max'] : 5 ) . '">'
                );
            }

            public function update_value( $value, $post_id, $field ) {
                return update_post_meta( $post_id, $field['key'], absint( $value ) );
            }

            public function load_value( $value, $post_id, $field ) {
                return absint( $value );
            }

            public function validate_value( $valid, $value, $field ) {
                return parent::validate_value( $valid, $value, $field );
            }
        }

        $dfp_fields->register_field_type( new My_Star_Rating_Field() );
    } );

== Hooks ==

= Actions =

* dfp/register_fields — register custom field types. Passes $dfp_fields registry instance.

= Filters =

* dfp/location/rule_params — add custom location rule params

    add_filter( 'dfp/location/rule_params', function( $params ) {
        $params['my_rule'] = 'My Custom Rule';
        return $params;
    } );

* dfp/location/rule_values — populate values for a custom rule param

    add_filter( 'dfp/location/rule_values', function( $values, $param ) {
        if ( $param === 'my_rule' ) {
            $values = [ 'a' => 'Option A', 'b' => 'Option B' ];
        }
        return $values;
    }, 10, 2 );

== Common Recipes ==

= Get field from a specific post =

    $title = get_field( 'hero_title', 5 );

= Get fields from a global settings page =

    $page_id = get_option( 'my_settings_page_id' );
    $phone   = get_field( 'contact_phone', $page_id );

= Output color as inline CSS variable =

    $color = get_field( 'brand_color' );
    if ( $color ) {
        echo '<style>:root{--brand-color:' . esc_attr( $color ) . ';}</style>';
    }

= Format a date picker value =

    $raw  = get_field( 'event_date' ); // "20260101"
    $date = $raw ? DateTime::createFromFormat( 'Ymd', $raw ) : null;
    if ( $date ) echo $date->format( 'F j, Y' ); // January 1, 2026

= Link to a related post =

    $post = get_field( 'related_post' ); // WP_Post
    if ( $post ) {
        echo '<a href="' . esc_url( get_permalink( $post ) ) . '">'
           . esc_html( $post->post_title ) . '</a>';
    }

= List taxonomy terms =

    $terms = get_field( 'featured_tags' ); // WP_Term[]
    foreach ( (array) $terms as $term ) {
        echo '<a href="' . esc_url( get_term_link( $term ) ) . '">'
           . esc_html( $term->name ) . '</a>';
    }

= Re-loop a repeater =

    if ( have_rows( 'items' ) ) {
        while ( have_rows( 'items' ) ) { the_row(); /* first pass */ }
    }
    reset_rows( 'items' );
    if ( have_rows( 'items' ) ) {
        while ( have_rows( 'items' ) ) { the_row(); /* second pass */ }
    }

= Gallery with lightbox =

    $images = get_field( 'gallery' );
    foreach ( (array) $images as $img ) {
        echo '<a href="' . esc_url( $img['url'] ) . '">'
           . '<img src="' . esc_url( $img['sizes']['medium'] ) . '"
                  alt="' . esc_attr( $img['alt'] ) . '">'
           . '</a>';
    }

= Downloadable file =

    $file = get_field( 'pdf_download' );
    if ( $file ) {
        echo '<a href="' . esc_url( $file['url'] ) . '" download>'
           . esc_html( $file['filename'] )
           . ' (' . esc_html( size_format( $file['filesize'] ) ) . ')'
           . '</a>';
    }

= Conditional section =

    if ( get_field( 'show_video_section' ) ) {
        get_template_part( 'template-parts/video' );
    }

== Frequently Asked Questions ==

= Does this conflict with ACF? =

No. All global template functions are wrapped in function_exists() checks. If ACF is active, its functions take precedence automatically. All class names use the DFP_ prefix.

= Where is field data stored? =

Each field's value is stored in wp_postmeta using the field key (e.g. field_abc123def456) as the meta key. Repeater and gallery values are stored as serialized PHP arrays.

= Can I import/export field groups? =

Yes. On the field groups list, click Export next to any group to download JSON. Use Import Field Group at the bottom of the page to import — keys are regenerated on import to prevent conflicts.

= Does it support the block editor / Gutenberg? =

Field groups appear as meta boxes below the editor. All 22 field types work through the standard WordPress meta box API.

= Can I use fields on taxonomy edit screens or user profiles? =

Location rules currently support post types, page templates, and post-level rules. Taxonomy and user profile support can be added via the location rule filters.

= How do I get the full image array vs just a URL? =

The image field returns an array by default: url, alt, width, height, sizes (thumbnail, medium, large). Set Return Format = URL to get just the URL string.

= How do I display a WYSIWYG field safely? =

Use wp_kses_post() or the_field() — never esc_html() as that would break HTML formatting.

    echo wp_kses_post( get_field( 'content' ) );
    // or:
    the_field( 'content' );

== Screenshots ==

1. Field Groups list
2. Field Builder — Fields tab
3. Field Builder — Location Rules tab
4. Field Builder — Settings tab
5. Meta box on post edit screen
6. Repeater field with multiple rows
7. Image field with media library
8. Gallery field with thumbnail grid
9. File field with file info
10. WYSIWYG editor field

== Changelog ==

= 1.0.0 =
* Initial release
* 22 field types: Text, Textarea, Number, Email, URL, Password, Select, Checkbox, Radio, True/False, Post Object, Relationship, Taxonomy, User, Date Picker, Color Picker, Image, Gallery, File, WYSIWYG Editor, Repeater
* Visual drag-to-reorder field builder
* Location rules engine with OR groups / AND rules
* REST API (GET + POST /wp-json/dfp/v1/fields/{post_id})
* Import / Export field groups as JSON
* ACF-compatible template-tag API
* Gallery field with wp.media multi-select, thumbnail grid, min/max limits
* File field with allowed mime types, size display, return format options
* WYSIWYG field with full TinyMCE, tabs/toolbar settings, safe clone fallback
* Nested repeater support

== Upgrade Notice ==

= 1.0.0 =
Initial release.
