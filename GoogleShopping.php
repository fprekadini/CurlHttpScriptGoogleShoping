<?php

error_reporting(0);

include('simple_html_dom.php');

class GoogleShopping {

    public function getPrices($ean) {

        $id = $ean;
        // This condition check if the EAN number has 14 digits, and adds 0 to the left
        if (strlen($id) < 15) {
            $id = str_pad($id, 14, "0", STR_PAD_LEFT);
            $id . '<br>';
        }

        $url = 'https://www.google.nl/search?hl=nl&output=search&tbm=shop&q=' . $id;
        $html = file_get_html($url);

        $full_url = '';

        $index = 0;
        foreach ($html->find('.r') as $t) {
            foreach ($t->find('a')as $key => $k) {
            // We had to take second product link because the first product was external link
                if ($index == 1) {
                    $full_url = 'https://www.google.nl/' . $k->href . '/online?hl=nl';
                }
                
                $index++;
            }
        }
        return curl($full_url);
    }

}

// Defining the basic cURL function
function curl($url) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_close($ch);

    if (!function_exists('curl_init')) {
        die('cURL is not installed. Install and try again.');
    }

    $dom = new DOMDocument('1.0');
    @$dom->loadHTMLFile($url);

    $anchors = $dom->getElementsByTagName('table');
    $arrayPrice = array(array());
    $i = -1;
    
    // Looping through the table content and getting neccessary data and returning into array
    foreach ($anchors as $tr) {
        foreach ($tr->childNodes as $td) {
            foreach ($td->childNodes as $ta) {
                foreach ($ta->attributes as $tc) {
                    if ($tc->value == 'os-seller-name' and $ta->tagName == 'td') {
                        $nameArticle = $ta->nodeValue;
                        $i++;
                    }
                    if ($tc->value == 'os-total-col' and $ta->tagName == 'td') {
                        $price = html_entity_decode($ta->nodeValue);
                        $price = preg_replace('/[^a-zA-Z0-9,\s€+-]+/', '', $price);
                    }
                }
                if ($nameArticle != '' and $price != '') {
                    $arrayPrice[$i]['seller'] = $nameArticle;
                    $arrayPrice[$i]['price'] = $price;
                }
            }
        }
    }
    $finalArray = array_filter(($arrayPrice));
    return $finalArray;

}
