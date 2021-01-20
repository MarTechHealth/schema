<?php
echo widget("lp-tools-php", "", $w["website_id"], $w);

if ($_POST["module_action"] != "") {
    switch ($_POST["module_action"]) {
        case 'get-site-subscriptions':
            $response = array(
                "subscriptions" => array()
            );
            $siteSubscriptionsQuerySQL = "SELECT subscription_id, 
                    subscription_name 
                FROM subscription_types 
                ORDER BY subscription_name ASC";
            $siteSubscriptionsQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'), $siteSubscriptionsQuerySQL);

            while ($subscription = mysql_fetch_assoc($siteSubscriptionsQuery)) {
                $response["subscriptions"][] = $subscription;
            }

            outputJSON("", "success", $response);
            break;
        case 'save-schema':
            $response = array(
                "save_status" => 0
            );
            if ($_POST["user_id"] != "") {
                // check if this user already has a schema
                $schemaCheckQuerySQL = "SELECT id 
                    FROM lp_user_schema 
                    WHERE user_id = '" . mysql_real_escape_string($_POST["user_id"]) . "'";
                $schemaCheckQuerySQL = mysql(brilliantDirectories::getDatabaseConfiguration('database'), $schemaCheckQuerySQL);

                if (mysql_num_rows($schemaCheckQuerySQL) > 0) {
                    $schemaCheck = mysql_fetch_assoc($schemaCheckQuerySQL);

                    if ($_POST["schema_content"] == "") {
                        $deleteSchemaQuerySQL = "DELETE FROM lp_user_schema  
                        WHERE user_id = '" . $_POST["user_id"] . "' 
                            AND id = '" . $schemaCheck["id"] . "'
                        LIMIT 1";
                        $deleteSchemaQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'), $deleteSchemaQuerySQL);
                        if (mysql_error($deleteSchemaQuery) == "") {
                            $response["save_status"] = 1;
                        }
                    } else {
                        $updateSchemaQuerySQL = "UPDATE lp_user_schema 
                        SET schema_content = '" . $_POST["schema_content"] . "', 
                            updated_at = '" . date("Y-m-d H:i:s") . "' 
                        WHERE user_id = '" . $_POST["user_id"] . "' 
                            AND id = '" . $schemaCheck["id"] . "'
                        LIMIT 1";
                        $updateSchemaQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'), $updateSchemaQuerySQL);
                        if (mysql_error($updateSchemaQuery) == "") {
                            $response["save_status"] = 1;
                        }
                    }
                } else {
                    if ($_POST["schema_content"] != "") {
                        $newSchemaQuerySQL = "INSERT INTO lp_user_schema 
                        SET user_id = '" . $_POST["user_id"] . "', 
                            schema_content = '" . mysql_real_escape_string($_POST["schema_content"]) . "', 
                            created_at = '" . date("Y-m-d H:i:s") ."'";
                        $newSchemaQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'), $newSchemaQuerySQL);
                    }
                    if (mysql_error($newSchemaQuery) == "" || $_POST["schema_content"] == "") {
                        $response["save_status"] = 1;
                    }
                }
            }
            outputJSON("", "success", $response);
            break;
        case 'schema-dynamic-table':
            $response = array(
                "request" => $_POST,
                "results" => array()
            );
            // Get all the media strategist this client has had
            $selectQueryParams = array(
                "ud.user_id",
                "ud.filename",
                "ud.company",
                "ud.phone_number",
                "ud.website",
                "ud.address1",
                "ud.city",
                "ud.state_code",
                "ud.zip_code",
                "ud.short_description",
                "st.subscription_name",
                "lus.schema_content",
                "lus.created_at AS schema_created_at",
                "lus.updated_at AS schema_updated_at",
                "udcat.user_categories"
            );
            $tableQueryParams = array(
                "users_data AS ud",
                "INNER JOIN subscription_types AS st ON ud.subscription_id = st.subscription_id",
                "LEFT JOIN lp_user_schema AS lus ON ud.user_id = lus.user_id",
                "LEFT JOIN ( SELECT GROUP_CONCAT(service_id) AS user_categories, user_id FROM rel_services GROUP BY user_id ) AS udcat ON ud.user_id = udcat.user_id"
            );
            $whereQueryParams = array();
            $groupByQueryParams = array();
            $orderByQueryParams = array(
                "lus.id DESC",
                "ud.user_id"
            );
            $sqlLimits = calculateSQLLimits($_POST["currentPage"], $_POST["resultsPerPage"]);

            if ($_POST["keyword"] != "") {
                $whereQueryParams[] = " (
                    ud.user_id LIKE '%".$_POST["keyword"]."%' OR
                    CONCAT(ud.first_name, ' ', ud.last_name) LIKE '%".$_POST["keyword"]."%' OR
                    ud.company LIKE '%".$_POST["keyword"]."%' OR
                    ud.email LIKE '%".$_POST["keyword"]."%' OR
                    ud.phone_number LIKE '%".$_POST["keyword"]."%' OR
                    CONCAT(ud.address1, ' ', ud.city, ' ', ud.zip_code, ' ', ud.state_ln) LIKE '%".$_POST["keyword"]."%' OR
                    lus.schema_content LIKE '%".$_POST["keyword"]."%'
                ) ";
            }

            if ($_POST["filter_schema"] == 1) {
                $whereQueryParams[] = " lus.id IS NOT NULL ";
            }

            if ($_POST["subscription_id"] != "") {
                $whereQueryParams[] = " ud.subscription_id = '" . $_POST["subscription_id"] . "' ";
            }

            if ($_POST["sortBy"] != "") {
                switch ($_POST["sortBy"]) {
                    case 'user-id':
                        if ($_POST["sort"] == "asc") {
                            $orderByQueryParams = array(
                                    "ud.user_id ASC"
                            );
                        } else {
                            $orderByQueryParams = array(
                                "ud.user_id DESC"
                            );
                        }
                        break;
                    case 'user-name':
                        if ($_POST["sort"] == "asc") {
                            $orderByQueryParams = array(
                                "ud.company ASC"
                            );
                        } else {
                            $orderByQueryParams = array(
                                "ud.company DESC"
                            );
                        }
                        break;
                    case 'susbcription-name':
                        if ($_POST["sort"] == "asc") {
                            $orderByQueryParams = array(
                                "st.subscription_name ASC"
                            );
                        } else {
                            $orderByQueryParams = array(
                                "st.subscription_name DESC"
                            );
                        }
                        break;
                }
            }

            $sqlConstruct = "SELECT SQL_CALC_FOUND_ROWS ";

            if ( count($selectQueryParams) > 0 ) {
                $sqlConstruct .= implode ( ", ", $selectQueryParams );
            }
            $sqlConstruct .= " FROM ";
            if ( count($tableQueryParams) > 0 ) {
                $sqlConstruct .= implode ( " ", $tableQueryParams );
            }
            if ( count($whereQueryParams) > 0 ) {
                $sqlConstruct .= " WHERE ";
                $sqlConstruct .= implode ( " AND ", $whereQueryParams );
            }
            if ( count($groupByQueryParams) > 0 ) {
                $sqlConstruct .= " GROUP BY ";
                $sqlConstruct .= implode ( ", ", $groupByQueryParams );
            }
            if ( count($orderByQueryParams) > 0 ) {
                $sqlConstruct .= " ORDER BY ";
                $sqlConstruct .= implode ( ", ", $orderByQueryParams );
            }
            $sqlConstruct .= " LIMIT ".$sqlLimits["start_limit"].", ".$sqlLimits["end_limit"];
            $resultsQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'), $sqlConstruct);
            $totalNumRowsQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'), "SELECT FOUND_ROWS() AS row_total");
            $total = mysql_fetch_assoc($totalNumRowsQuery);
            $response["total_results"] = $total["row_total"];
            $resultRecords = array();
            while ($record = mysql_fetch_assoc($resultsQuery)) {
                $record["user_info"] = getDisplayUserInfo($record["user_id"]);
                $record["schema_created_at"] = date("D j M, Y", strtotime($record["schema_created_at"]));
                if ($record["schema_updated_at"] != "0000-00-00 00:00:00" && $record["schema_updated_at"] != NULL) {
                    $record["schema_updated_at"] = date("H:i a, D j M, Y", strtotime($record["schema_updated_at"]));
                }

                $userReviewsQuerySQL = "SELECT review_id,
                        member_id,
                        tce_anon_review,
                        review_added,
                        review_description,
                        review_token
                    FROM users_reviews WHERE user_id = '" . $record["user_id"]. "'";
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
                $servicesFormattedFirst = explode(",", $record["user_categories"]);
                // get the services
                $ServicesInfoQuerySQL = "SELECT GROUP_CONCAT(ls.name) AS all_categories
                    FROM rel_services AS rs 
                        INNER JOIN list_services AS ls ON rs.service_id = ls.service_id 
                    WHERE rs.user_id = '" . $record["user_id"] . "' 
                        AND rs.service_id NOT IN (117, 118, 119)";
                $ServicesInfoQuery = mysql(brilliantDirectories::getDatabaseConfiguration('database'), $ServicesInfoQuerySQL);
                $ServicesInfo = mysql_fetch_assoc($ServicesInfoQuery);
                $servicesFormattedSecond = explode(",", $ServicesInfo["all_categories"]);

                ob_start();
                ?>
                    <script type="application/ld+json">
                        {
                            "@context": "http://schema.org",
                            "@type": "Organization",
                            "mainEntityOfPage": {
                                "@type": "WebPage",
                                "url": "https://<?php echo $w["website_url"]."/".$record["filename"]; ?>",
                                "isPartOf": {
                                    "@type": "WebSite",
                                    "name": "Martech Health Directory",
                                    "url": "https://martech.health/"
                                }
                            },
                            "name": "<?php echo $record["company"]; ?>",
                            "logo": "<?php echo $record["user_info"]["image_main_file"]; ?>",
                            "image": "<?php echo $record["user_info"]["image_main_file"]; ?>",
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
                            "telephone": "<?php echo $record["phone_number"]; ?>",
                            "description": "<?php echo $record["short_description"]; ?>",
                            "url": "<?php echo urldecode($record["website"]); ?>",
                            "sameAs": "<?php echo urldecode($record["website"]); ?>",
                            "address": {
                                "@type": "PostalAddress",
                                "streetAddress": "<?php echo $record["address1"]; ?>",
                                "addressLocality": "<?php echo $record["city"]; ?>",
                                "addressRegion": "<?php echo $record["state_code"]; ?>",
                                "postalCode": "<?php echo $record["zip_code"]; ?>"
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
                $record["default_schema"] = ob_get_contents();
                ob_end_clean();

                $resultRecords[] = $record;
            }
            $response["results"] = $resultRecords;

            outputJSON("", "success", $response);
            break;
    }
}
?>