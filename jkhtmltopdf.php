<?php
    /**
    * Plugin Name: JK Html To PDF
    * Description: Html To PDF using wkhtmltopdf.
    * Version: 1.0.0
    * Author: Jay Krishnan G
    */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include_once('support/wkhtmltopdf.php');

$jk_hpdf_settings = unserialize(get_option('jk_hpdf_settings'));

$wk_path =  isset($jk_hpdf_settings["wk_path"]) ? $jk_hpdf_settings["wk_path"] : "";

define('WKHTMLPATH', $wk_path);

$upload = wp_upload_dir();

$jk_hp_upload_dir = $upload['basedir'].'/jkhtmltopdf/';
$jk_hp_upload_url = $upload['baseurl'].'/jkhtmltopdf/';

define('JK_HPDF_UPLOAD_DIR', $jk_hp_upload_dir);

define('JK_HPDF_UPLOAD_URL', $jk_hp_upload_url);

// if (!defined('WKHTMLPATH'))
//     define('WKHTMLPATH', '/usr/bin/wkhtmltopdf.sh'); //Update the path

/**
 * Plugin Activation
 */
function jk_hpdf_activation()
{
    $upload_html_dir = JK_HPDF_UPLOAD_DIR . 'html';
    $upload_pdf_dir = JK_HPDF_UPLOAD_DIR . 'pdf';
    if (! is_dir($upload_html_dir)) {
        $old_umask = umask(0);
        mkdir( $upload_html_dir, 0777, 1);
        umask($old_umask);
    }
    if (! is_dir($upload_pdf_dir)) {
        $old_umask = umask(0);
        mkdir( $upload_pdf_dir, 0777, 1);
        umask($old_umask);
    }
}

register_activation_hook(__FILE__, 'jk_hpdf_activation');

/**
 * Plugin Deactivation
 */
function jk_hpdf_deactivation()
{
    delete_option('jk_hpdf_settings');
    $upload_dir = JK_HPDF_UPLOAD_DIR;
    deleteDir($upload_dir);
}

register_deactivation_hook(__FILE__, 'jk_hpdf_deactivation');

/**
 * Delete directory
 * @param string $path
 */
function deleteDir($path) {
    return is_file($path) ?
    @unlink($path) :
    array_map(__FUNCTION__, glob($path.'/*')) == @rmdir($path);
}


add_action ( 'admin_menu', 'jk_hpdf_settings' );

/**
 * Html to pdf settings menu
 */
function jk_hpdf_settings() {
    add_menu_page ( 'JK Html To Pdf', 'JK Html To Pdf', 'administrator', 'jk_hpdf_settings', 'jk_hpdf_show_settings' );
}

/**
 * Html to pdf settings
 */
function jk_hpdf_show_settings()
{
    $message = "";
    if($_POST)
    {
        $jk_hpdf_settings = array();
        
        if(isset($_POST['wk_path']) && ($_POST['wk_path']))
            $jk_hpdf_settings['wk_path'] = sanitize_text_field( $_POST['wk_path'] );
        
        if(!empty($jk_hpdf_settings))
        {
            $jk_hpdf_settings = serialize($jk_hpdf_settings);
            $updated = update_option('jk_hpdf_settings', $jk_hpdf_settings);
            $message .= "<div class='updated fade'><p><strong>Html to Pdf Settings Saved.</strong></p></div>";
        }
    }
    
    $jk_hpdf_settings = unserialize(get_option('jk_hpdf_settings'));
    
    $wk_path =  isset($jk_hpdf_settings["wk_path"]) ? $jk_hpdf_settings["wk_path"] : "";
?>
<style>
.inner_wrapper {
    float: left;
    width: 98%;
    margin: 1% 1%;
}
</style>
</pre>
<div class="wrap">
    <form action="" method="post" name="options">
        <h3>JK Html To Pdf Settings</h3>
    <?php
    echo $message;
    ?>
    <div class="inner_wrapper">
            <table class="form-table wp-list-table widefat striped" width="100%"
                cellpadding="10" size="40" id="htmltopdf_table">
                <tbody>
                    <tr>
                        <td scope="row" align="left" width="20%"><label>Wktohtml Executable Path:</label>
                        </td>
                        <td><input type="text" name="wk_path" id="wk_path" size="40"
                            value="<?php echo $wk_path; ?>" /></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="inner_wrapper">
            <input type="submit" name="Submit" value="Update"
                class="button-primary" />
        </div>
    </form>
</div>
<pre>
<?php
}

/**
 * Generate Pdf
 *
 * @param string $content
 *            html content
 *
 * @return string file path url
 */
function jk_hpdf_generate_pdf($content) {
    
    $pdf = new WkHtmlToPdf ();
    
    $pdf->setOptions ( array (
            'no-outline', // Make Chrome not complain
            'margin-top' => 25,
            'margin-right' => 20,
            'margin-bottom' => 25,
            'margin-left' => 20,

            // Default page options
            'disable-smart-shrinking',
    ));

    $html_file_path = jk_hpdf_create_html_file($content);

    $pdf->addPage($html_file_path);

    $pdf_name = md5(microtime());

    $pdf_path = JK_HPDF_UPLOAD_DIR."pdf/".$pdf_name.".pdf";

    $file_path = JK_HPDF_UPLOAD_URL."pdf/".$pdf_name.".pdf";

    $saved = $pdf->saveAs($pdf_path);

    if($saved) {
        echo $file_path;
    }
}

//add_shortcode( 'htmltopdf_generate_pdf' , 'generate_pdf' );

/**
 * Create html content
 *
 * @param string $content
 *
 * @return string html file path
 */
function jk_hpdf_create_html_file($content)
{
    $html_file_name = md5(microtime()).".html";
    $html_file_path = JK_HPDF_UPLOAD_DIR."html/".$html_file_name;
    
    $fh = fopen($html_file_path, 'w'); // or die("error");
    $fw = fwrite($fh, $content);

    if($fw)
    {
        return $html_file_path;
    }
}
