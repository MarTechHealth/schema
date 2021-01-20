<?php
//Redirects and Setup
//Members Only Rule
if ($_COOKIE["userid"] > 0) {
    $checkUserActiveQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'),"SELECT
            active
        FROM
            `users_data`
        WHERE
            user_id = '".$_COOKIE[userid]."'");

    if (mysql_num_rows($checkUserActiveQuery) > 0) {
        $checkUserActive = mysql_fetch_assoc($checkUserActiveQuery);
        if ($checkUserActive["active"] == 3) {
            $loginStatus = 0;
        } else {
            $loginStatus = 1;
        }
    } else {
        $loginStatus = 0;
    }
    if ($loginStatus == 0) {
        echo '<script>window.location="/logout";</script>';
        exit;
    }
}

if (($w["home_tagline"] == 1 || $page["content_footer"] == 1) && $_COOKIE["userid"] == '') {

    $parsArray = array("account","login","about","tour","contact","info","join","home","payment","checkout","getmatched","passwordreset","join-today","members-join");

    if(isset($w['members_only_pages'])){
        $parsArray = explode(',', $w['members_only_pages']);
    }

    if (!in_array($pars[0],$parsArray) || $page["content_footer"] == 1) {
        echo '<script>window.location="/login?members_only";</script>';
        exit;
    }
}
// Set protocol
$https_on = 0;

if (isset($_SERVER['HTTPS']) && $_SEREVER['HTTPS'] != 'off') {
    $http = 'https://';
    $https_on = 1;

} else {
    $http = 'http://';
    $https_on = 0;
}
// Set Membership Feature Currency
if ($w["member_feature_currency"] != "") {
    $w["currency_symbol"] = html_entity_decode( $w['member_feature_currency'], ENT_QUOTES, 'UTF-8');
}


$tceCatInfoDetails = "";

if (($_GET["tid"] != "" && strpos($_GET["tid"],",") == false)
    ||
    $_GET["ttid"] != ""
    ||
    $service["service_id"] != ""
    ||
    ($profs["service_id"] != "" && strpos($_GET["tid"],",") == false)) {

    if ($_GET["ttid"] != "") {
        $tceCatInfo = $_GET["ttid"];

    } else if ($_GET["tid"] != "") {
        $tceCatInfo = $_GET["tid"];

    } else if ($service["service_id"] != "") {
        $tceCatInfo = $service["service_id"];

    } else if  ($profs["service_id"] != "") {
        $tceCatInfo = $profs["service_id"];
    }
    $tceCatInfoQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'),"SELECT
            *
        FROM
            tce_categories_descriptions
        WHERE
            category_id = '".$tceCatInfo."'
        AND
            (category_lvl = 'sub_category' OR category_lvl = 'sub_sub_category')");
    $tceCatInfoDetails = mysql_fetch_assoc($tceCatInfoQuery);

} else if ($_GET["sid"] != "" || $profession['profession_id'] != "") {

    if ($_GET["sid"] != "") {
        $tceCatInfo = $_GET["sid"];

    } else if ($profession['profession_id'] != "") {
        $tceCatInfo = $profession['profession_id'];
    }
    $tceCatInfoQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'),"SELECT
            *
        FROM
            tce_categories_descriptions
        WHERE
            category_id = '".$tceCatInfo."'
        AND
            category_lvl = 'top_category'");
    $tceCatInfoDetails = mysql_fetch_assoc($tceCatInfoQuery);
}

if ($tceCatInfoDetails != "" && $page["seo_type"] != "profile") {

    if ($tceCatInfoDetails["category_title"] != "") {
        $page["title"] = $tceCatInfoDetails["category_title"];
        $og_title = $tceCatInfoDetails["category_title"];
    }

    if ($tceCatInfoDetails["category_keywords"] != "") {
        $page["meta_keywords"] = $tceCatInfoDetails["category_keywords"];
    }

    if ($tceCatInfoDetails["meta_description"] != "") {
        $og_desc = strip_tags($tceCatInfoDetails["meta_description"]);
        $page["meta_desc"] = strip_tags($tceCatInfoDetails["meta_description"]);
    }
}

//// Set all of the most appropriate meta data sources
// Set Follow or NoFollow
$meta_robots = 'index, follow';

if  ($page["show_form"]== "1" || $w["website_active"]== 2 || $user["active"]== "0" || $user["active"]== "1" || $user["active"]== "3" || $user["active"]== "4" || ($subscription["index_rule"] == 1 && $page["seo_type"] == 'profile')) {
    $meta_robots = 'noindex, nofollow';

} else if ($page["meta_robots"] != '') {
    $meta_robots = $page["meta_robots"];

} else {
    $meta_robots = $w["meta_robots"];
}
// Set Canonical Link
$app_uri = $http . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

if ($pos_get = strpos($app_uri, '?')) {
    $app_uri = substr($app_uri, 0, $pos_get);
}
$app_uri = str_replace(".php","",$app_uri);
$app_uri = str_replace(".html","",$app_uri);
$app_uri = str_replace(".htm","",$app_uri);

if ($pars[0] == 'home') {
    $meta_canonical = '<link rel="canonical" href="' . $http . $_SERVER['HTTP_HOST'] . '">';

} else if ($page["filename"] != '') {

    if ($page['template_id'] == 203) {
        $page['filename'] = $pars[0] . "/" . $pars[1];
    }
    if (array_key_exists("page", $_GET)) {
        $page["filename"] = $page["filename"]."?page=".htmlspecialchars($_GET['page']);
    }
    $meta_canonical = '<link rel="canonical" href="' . $http . $_SERVER['HTTP_HOST'] . '/' . $page["filename"] . '">';

} else if (isset($app_uri) && $page["filename"] == '') {

    if (array_key_exists("page", $_GET)) {
        $app_uri = $app_uri."?page=".htmlspecialchars($_GET['page']);
    }
    $meta_canonical = '<link rel="canonical" href="' . $app_uri . '">';
}

if ($post["post_meta_canonical"] != "") {
    $meta_canonical = '<link rel="canonical" href="' . $post["post_meta_canonical"] . '">';
}
// Favicon
if ($w["favicon"]!='') {
    $meta_fav_url = $w["favicon"];

} else if (file_exists('/home/$w[website_user]/public_html/favicon.ico')) {
    $meta_fav_url = '/favicon.ico';

}else if ($https_on == 1 && $w["favicon"] == '') {
    $meta_fav_url = '/images/secure-favicon.ico';

} else {
    $meta_fav_url = '/images/favicon.png';
}
$meta_fav = '<link rel="shortcut icon" href="' . $meta_fav_url . '">';
//// Set all of the most appropriate social media sources
// Social media URL
if ($page["seo_type"] == 'home') {
    $og_url = $http . $_SERVER['HTTP_HOST'];

} else if ($page["filename"] != "") {
    $og_url = $http . $_SERVER['HTTP_HOST'] . '/' . $page["filename"];

} else if ($fb["url"] == '') {
    $og_url = $http . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

} else {
    $og_url = $fb["url"];
}
// Social media title
if ($tceCatInfoDetails == "") {

    if ($post["post_meta_title"] != "") {
        $og_title = $post["post_meta_title"];

    } else if ($page["facebook_title"] != '') {
        $og_title = $page["facebook_title"];

    } else if ($group["group_name"] != '') {
        $og_title = $group["group_name"];

    } else if ($post["post_title"] != '') {
        $og_title = $post["post_title"];

    } else if ($page["title"] != '') {
        $og_title = $page["title"];

    } else if ($page["h1"] != '') {
        $og_title = $page["h1"];
    }
}
// Social media description
if ($tceCatInfoDetails == "") {

    if ($post["post_meta_description"] != "") {
        $og_desc = $post["post_meta_description"];

    } else if ($page["facebook_desc"] != '') {
        $og_desc = $page["facebook_desc"];

    } else if ($group["group_desc"] != '') {
        $og_desc = $group["group_desc"];

    } else if ($post["post_content"] != '') {
        $og_desc = $post["post_content"];

    } else {
        $og_desc = $page["meta_desc"];
    }
}
// Clean social media description
$og_desc = stripslashes(str_replace('"', '', $og_desc));
// Social media sharing image
$og_outside_image = 0;

if ($page["facebook_image"] != '') {
    $og_image = $page["facebook_image"];

} else if ($user["listing_type"] == 'Company' && $user["logo_file"] != '' && $page["seo_type"] == 'profile') {
    $og_image = $user["logo_file"];
    //check if the new images implementation has been done
    $checkDataFlowsTableQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'),'SELECT
            1
        FROM
            `image_settings`
        LIMIT
            1');

    if ($checkDataFlowsTableQuery !== FALSE) {
        $checkPath = str_replace("/profile/","/social_media/",$user["logo_file"]);

        //check if social media version exists
        if (file_exists("/home/".$w["website_user"]."/public_html".$checkPath)) {
            $og_image = $checkPath;
        }
    }

} else if ($photo["file"] != '' && $page["seo_type"] == 'profile') {
    $og_image = $photo["file"];
    //check if the new images implementation has been done
    $checkDataFlowsTableQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'),'SELECT
            1
        FROM
            `image_settings`
        LIMIT
            1');

    if ($checkDataFlowsTableQuery !== FALSE) {
        $checkPath = str_replace("/profile/","/social_media/",$photo["file"]);

        //check if social media version exists
        if (file_exists("/home/".$w["website_user"]."/public_html".$checkPath)) {
            $og_image = $checkPath;
        }
    }

} else if ($post["post_image"] != '') {
    $og_image = $post["post_image"];

} else if ($post["post_video"] != '') {
    $video_link = $post["post_video"];
    $video_url = str_replace( 'https://', 'http://', $video_link );

    if (strpos($video_url, "youtube")) {
        $cart =  substr($video_url , 31);
        $video_img = "http://img.youtube.com/vi/$cart/0.jpg";
    }
    if (strpos($video_url, "youtu.be")) {
        $cart =  substr($video_url , 16);
        $video_img = "http://img.youtube.com/vi/$cart/0.jpg";
    }
    if (strpos($video_url, "vimeo")) {  //vimeo
        $cart = explode("/",$url);
        $cartLength = count($cart) - 1;
        $imgid = $cart[$cartLength];
        $url = "http://vimeo.com/api/v2/video/".$imgid.".json";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $curlData = curl_exec($curl);
        curl_close($curl);
        $hash = current(json_decode($curlData, true));
        $video_img = $hash['thumbnail_medium'];
    }
    $og_image = $video_img;
    $og_outside_image = 1;

} else if ($group[group_id] != '') {
    $group_image_query = mysql(brilliantDirectories::getDatabaseConfiguration('database'),"SELECT
            `file`
        FROM
            `users_portfolio`
        WHERE
            `group_id` = '".$group["group_id"]."'
        AND
            `file` != ''
        ORDER BY
            `order` ASC
        LIMIT
            1");
    $group_image = mysql_fetch_assoc($group_image_query);
    $og_image = '/' . $w["photo_folder"] . '/main/' . $group_image["file"];

} else if (isset($_ENV["results_loop_image"][0]) && $_ENV["results_loop_image"][0] != '') {
    $og_image = '/' . $w["photo_folder"] . '/main/' . $_ENV["results_loop_image"][0];

} else if ($p["file"] != '') {
    $og_image = '/' . $w["photo_folder"] . '/main/' . $p["file"];

} else if ($w["facebook_app_id"] != '') {
    $og_image = $w["facebook_app_id"];

} else if ($wa["custom_82"] != '' && $wa["custom_78"] == '1') {
    $og_image = $wa["custom_82"];

} else if ($wa["custom_76"] != '') {
    $og_image = $wa["custom_76"];

} else if ($w["website_logo"] != '') {
    $og_image = $w["website_logo"];

} else {
    $og_image = '/images/slide2.jpg';
}
//Blog post meta data
if ($post['post_meta_description'] != "" && $pars[1] != '' && $page['seo_type'] != 'profile') {
    $page['meta_desc'] = $post['post_meta_description'];
}
if ($post['post_meta_title'] != "" && $pars[1] != '' && $page['seo_type'] != 'profile') {
    $page['title'] = $post['post_meta_title'];
}
if ($post['post_meta_keywords'] != "" && $pars[1] != '' && $page['seo_type'] != 'profile') {
    $page['meta_keywords'] = $post['post_meta_keywords'];
}
// Clean the image URL
if ($og_outside_image != 1) {
    $og_image = str_replace('http://','',$og_image);
    $og_image = str_replace($w["website_url"],'',$og_image);
    $og_image = $http . $_SERVER['HTTP_HOST'] . $og_image;
} ?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php echo substr($w['website_language'],0,2); ?>">
<head>
    <?php echo widget("lp-profile-google-schema", "", $w["website_id"], $w); ?>
    <!-- Site Meta Data -->
    <?php
    //we decode title in case is encode
    $filenameBdString = new bdString($page['title']);
    $filenameBdString->urldecode();
    $page['title'] = $filenameBdString->modifiedValue;

    //we decode the og:title in case is encode
    $filenameBdString->originalValue = $og_title;
    $filenameBdString->rollBack();
    $filenameBdString->urldecode();
    $og_title = $filenameBdString->modifiedValue;
    ?>
    <title><?php echo $page["title"]; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="description" content="<?php echo $page["meta_desc"]; ?>">
    <meta name="keywords" content="<?php echo $page["meta_keywords"]; ?>">
    <meta name="robots" content="<?php echo $meta_robots; ?>">
    <!-- Social Media Meta Data -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $og_url; ?>">
    <meta property="og:site_name" content="<?php echo $w["website_name"]; ?>">
    <meta property="og:title" content="<?php echo $og_title; ?>">
    <meta property="og:description" content="<?php echo $og_desc; ?>">
    <meta property="og:image" content="<?php echo $og_image; ?>">
    <?php
    $rssFeedAddon = getAddOnInfo('rss_feed');
    if (isset($rssFeedAddon['status']) && $rssFeedAddon['status'] === 'success'){
        ?>
        <link rel="alternate" type="application/rss+xml" title="<?php echo $w['website_name']; ?> RSS Feed" href="<?php echo $http.$w['website_url']; ?>/rss" />
    <?php } ?>
    <link rel="alternate" hreflang="<?php echo $w['website_language']; ?>" href="<?php echo $http.$w['website_url'].$_SERVER['REQUEST_URI']; ?>" />
    <!-- Canonical Link and Rel Links -->
    <?php echo $meta_canonical; echo $rel_links["prev"]; echo $rel_links["next"]?>
    <!-- Google Font API -->
    <?php
    $websiteFonts = array_unique(array_filter(array($wa['custom_3'], $wa['custom_13'], $wa['custom_27'], $wa['custom_68'], $wa['custom_208'],$wa['announcement_bar_addon_ffamily'])));
    $websiteFonts = implode($websiteFonts, ':300,400,600|');
    $websiteFonts .=':300,400,600';
    $websiteFonts = preg_replace("/ /", "+", $websiteFonts);
    ?>
    <link href='//fonts.googleapis.com/css?family=<?php echo $websiteFonts; ?>' rel='stylesheet' type="text/css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" integrity="sha384-pdapHxIh7EYuwy6K7iE41uXVxGCXY0sAjBzaElYGJUrzwodck3Lx6IE2lA0rFREo" crossorigin="anonymous">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
    <!-- Theme CSS -->
    <link rel="stylesheet" href="/directory/cdn/assets/bootstrap/css/theme-styles.min.css?v=1.1.05">
    <!-- Froala Editor Front-End CSS -->
    <link rel="stylesheet" href="/directory/cdn/assets/bootstrap/css/froala_style.min.css">
    <!-- jQuery UI CSS -->
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.min.css" integrity="sha384-uKZHvTXs9A65QejCOMRfbxH37eCn6yo7LB91qSYHgRXpUdcXp6+ZQyFmCZeyz8WC" crossorigin="anonymous">
    <!-- jQuery JS -->
    <script src="/directory/cdn/assets/bootstrap/js/jquery.min.js"></script>
    <!-- jQuery UI JS -->
    <script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js" integrity="sha384-YWP9O4NjmcGo4oEJFXvvYSEzuHIvey+LbXkBNJ1Kd0yfugEZN9NCQNpRYBVC1RvA" crossorigin="anonymous"></script>
    <?php
    if ($wa["custom_78"] == "1") {
        echo '<!-- Background Slider -->
                <script defer src="/directory/cdn/assets/bootstrap/js/BGSlider.min.js"></script>
                <link rel="stylesheet" href="/directory/cdn/assets/bootstrap/css/BGSlider.min.css">';
    } ?>
    <!-- Bootstrap JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js" integrity="sha384-pPttEvTHTuUJ9L2kCoMnNqCRcaMPMVMsWVO+RLaaaYDmfSP5//dP6eKRusbPcqhZ" crossorigin="anonymous"></script>
    <script src="/directory/cdn/admin/image-validator/image-validator.min.js?v=2.0"></script>




    <!-- Favicon -->
    <?php
    echo $meta_fav;
    // Add custom HEAD content
    $custom_head = '';

    if ($wa["website_head"] != '') {
        echo '<!-- Custom Site-wide HEAD Content -->';
        echo eval("?>".trim($wa["website_head"])."<?") . '';
    }
    if ($page["content_head"]!='') {
        echo '<!-- Custom Page HEAD Content -->';
        echo eval("?>".trim($page["content_head"])."<?") . '';
    }
    if ($usetting["content_head"]!='') {
        echo '<!-- Custom Content HEAD Content -->';
        echo eval("?>".trim($usetting["content_head"])."<?") . '';
    }
    if ($w["google_analytics"] != "") { ?>
        <!-- Google Analytics Tracking Code -->
        <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

            ga('create', '<?php echo $w["google_analytics"]; ?>', 'auto');
            ga('send', 'pageview');
        </script>
        <?php
    } ?>
    [widget=Bootstrap Theme - Function - Google Location Suggest]
</head>
<?php
// Code that redirects members to the next active tab in their member management settings.
$locationCheck = $pars[0].'/'.$pars[1];

if (is_null($subscription["show_contact_detail"])) {
    $subscription["show_contact_detail"] = 1;
    $subscription["show_logo_upload"] = 1;
    $subscription["show_profile_photo"] = 1;
    $subscription["show_listing_details"] = 1;
    $subscription["show_about_tab"] = 1;
    $subscription["location_limit"] = 0;
}
$contactTab = $subscription["show_contact_detail"];
$listingTab = $subscription["show_listing_details"];
$aboutTab = $subscription["show_about_tab"];

if ($subscription["location_limit"] == '' || $subscription["location_limit"] == 0) {
    $serviceTab = 0;

} else {
    $serviceTab = 1;
}
if ($subscription["show_logo_upload"] == 0 && $subscription["show_profile_photo"] == 0) {
    $photoTab = 0;

} else {
    $photoTab = 1;
}
if ($locationCheck == "account/contact") {

    if ($contactTab == 0) {

        if ($photoTab == 1) {
            header("Location: /account/profile");
            break;

        } else if ($listingTab == 1) {
            header("Location: /account/resume");
            break;

        } else if ($aboutTab == 1) {
            header("Location: /account/about");
            break;

        } else if ($serviceTab == 1) {
            header("Location: /account/locations");
            break;

        } else {
            header("Location: /account/home");
        }
    }
}
if ($locationCheck == "account/profile") {

    if ($photoTab == 0) {

        if ($listingTab == 1) {
            header("Location: /account/resume");
            break;

        } else if ($aboutTab == 1) {
            header("Location: /account/about");
            break;

        } else if ($serviceTab == 1) {
            header("Location: /account/locations");
            break;

        } else {
            header("Location: /account/home");
        }
    }
}
if ($locationCheck == "account/resume") {

    if ($listingTab == 0) {

        if ($aboutTab == 1) {
            header("Location: /account/about");
            break;

        } else if ($serviceTab == 1) {
            header("Location: /account/locations");
            break;

        } else {
            header("Location: /account/home");
        }
    }
}
if ($locationCheck == "account/about") {

    if ($aboutTab == 0) {

        if ($serviceTab == 1) {
            header("Location: /account/locations");
            break;

        } else {
            header("Location: /account/home");
        }
    }
}
if ($locationCheck == "account/locations") {

    if ($serviceTab == 0) {
        header("Location: /account/home");
    }
}
?>
