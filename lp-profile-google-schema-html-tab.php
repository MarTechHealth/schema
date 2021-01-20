<?php
if ($page["seo_type"] == "profile") {
    
    // check if this user as a custom user schema
    $customSchemaQuerySQL = "SELECT schema_content 
        FROM lp_user_schema 
        WHERE user_id = '" . $user["user_id"] . "' 
            AND schema_content != ''";

    $customSchemaQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'), $customSchemaQuerySQL);

    if (mysql_num_rows($customSchemaQuery) > 0) {
        $customSchema = mysql_fetch_assoc($customSchemaQuery);

        echo $customSchema["schema_content"];

    } else {
        $userReviewsQuerySQL = "SELECT review_id,
            member_id,
            tce_anon_review,
            review_added,
            review_description,
            review_token
        FROM users_reviews WHERE user_id = '" . $user["user_id"] . "'";
        $userReviewsQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'), $userReviewsQuerySQL);
        $reviewsCount = 0;
        $reviewScoreSum = 0;
        $reviewsInfo = array();

        while ($parentReview = mysql_fetch_assoc($userReviewsQuery)) {
            $reviewsCount++;
            $scoreSum = 0;
            $reviewCategoryRatingsQuerySQL = "SELECT review_score
            FROM tce_review_category
            WHERE review_token = '" . $parentReview["review_token"] . "'";
            $reviewCategoryRatingsQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'), $reviewCategoryRatingsQuerySQL);
            $scoreCount = mysql_num_rows($reviewCategoryRatingsQuery);
            while ($singleScore = mysql_fetch_assoc($reviewCategoryRatingsQuery)) {
                $scoreSum += $singleScore["review_score"];
            }
            $reviewScoreSum += ( $scoreSum / $scoreCount);

            $reviewerInfo = getUser($parentReview["member_id"], $w);

            $vocals = array('a','e','i','o','u');

            if (in_array(strtolower(substr($reviewerInfo['type_of_org'],0,1)), $vocals)) {
                $submitByA = "an";

            } else {
                $submitByA = "a";
            }
            $reviewerName = "";

            if ($reviewerInfo["subscription_id"] == 5) {

                if ($parentReview["tce_anon_review"] == 0) {
                    $reviewerName = $label['by_label']." ".ucwords($reviewerInfo["first_name"])." ".$reviewerInfo["last_name"]. " at " . $reviewerInfo['company'];

                } else {
                    $reviewerName = "by ".$submitByA." ".$reviewerInfo['type_of_org']. " professional" ;
                }

            } else {
                $reviewerName = $label['by_label']." " . $reviewerInfo["company"];
            }


            $reviewsInfo[] = array(
                "review_added" => date("Y-m-d", strtotime($parentReview["review_added"])),
                "reviewer_name" => $reviewerName,
                "review_score" => (int) ( $scoreSum / $scoreCount),
                "review_description" =>  strip_tags($parentReview["review_description"])
            );
        }
        if ($reviewsCount == 0) {
            $ratingValue = 0;
        } else {
            $ratingValue = $reviewScoreSum / $reviewsCount;
        }
        $reviewsFormatted = array();

        foreach ($reviewsInfo AS $rvKey => $rvValue) {
            $reviewsFormatted[] = array(
                "@type" => "Review",
                "author" => array(
                    "@type" => "Person",
                    "name" => $rvValue["reviewer_name"]
                ),
                "datePublished" => $rvValue["review_added"],
                "reviewRating" => array (
                    "@type" => "Rating",
                    "bestRating" => "5",
                    "worstRating" => "1",
                    "ratingValue" => (string) $rvValue["review_score"]
                ),
                "reviewBody" => $rvValue["review_description"]
            );
        }
        $servicesFormattedFirst = explode(",", $user["services"]);
        // get the services
        $ServicesInfoQuerySQL = "SELECT GROUP_CONCAT(ls.name) AS all_categories
        FROM rel_services AS rs 
            INNER JOIN list_services AS ls ON rs.service_id = ls.service_id 
        WHERE rs.user_id = '" . $user["user_id"] . "' 
            AND rs.service_id NOT IN (117, 118, 119)";
        $ServicesInfoQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'), $ServicesInfoQuerySQL);
        $ServicesInfo = mysql_fetch_assoc($ServicesInfoQuery);
        $servicesFormattedSecond = explode(",", $ServicesInfo["all_categories"]);

        ?>
        <script type="application/ld+json">
            {
                "@context": "http://schema.org",
                "@type": "Organization",
                "mainEntityOfPage": {
                    "@type": "WebPage",
                    "url": "https://<?php echo $w["website_url"]."/".$user["filename"]; ?>",
                    "isPartOf": {
                        "@type": "WebSite",
                        "name": "Martech Health Directory",
                        "url": "https://martech.health/"
                    }
                },
                "name": "<?php echo $user["company"]; ?>",
                "logo": "https://<?php echo $w["website_url"].$user["image_main_file"]; ?>",
                "image": "https://<?php echo $w["website_url"].$user["image_main_file"]; ?>",
                <?php
            if ($reviewsCount > 0) { ?>
                    "aggregateRating": {
                        "@type": "AggregateRating",
                        "ratingValue": "<?php echo (int) $ratingValue; ?>",
                        "reviewCount": "<?php echo $reviewsCount; ?>"
                    },
                    <?php
            }
            ?>
                "telephone": "<?php echo $user["phone_number"]; ?>",
                "description": "<?php echo $user["short_description"]; ?>",
                "url": "<?php echo $user["website"]; ?>",
                "sameAs": "<?php echo $user["website"]; ?>",
                "address": {
                    "@type": "PostalAddress",
                    "streetAddress": "<?php echo $user["address1"]; ?>",
                    "addressLocality": "<?php echo $user["city"]; ?>",
                    "addressRegion": "<?php echo $user["state_code"]; ?>",
                    "postalCode": "<?php echo $user["zip_code"]; ?>"
                },
                "additionalProperty": {
                    "@type": "PropertyValue",
                    "name": "Specialties",
                    "value": ["<?php echo implode('","', $servicesFormattedSecond)?>"]
                },
                "review" : <?php echo json_encode($reviewsFormatted); ?>
            }
        </script>
        <?php
    }
} ?>