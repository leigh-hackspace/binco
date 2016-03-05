<?php
require_once 'vendor/autoload.php';

use GuzzleHttp\Client;

class ScrapeBinDetails {
    
    public function __construct($UPRN)
    {
        $this->UPRN = $UPRN;
        $this->httpclient = new GuzzleHttp\Client();
    }

    public function getDetails()
    {
        try {
            $response = $this->httpclient->request('POST', 'http://kinnear.wigan.gov.uk/bincollections/Default.aspx', array(
                'form_params' => array(
                    'lbAddresses' => $this->UPRN,
                    '__VIEWSTATE' => require_once 'UPRNViewState.php',
                    '__EVENTVALIDATION' => '/wEWKwK+wYPRCwL++IESAuGVvaUCAsvbsOcMAor0rfsEAor0rfsEAtflk4oOAtfl/7cHAvKOltYLAvKO4vMMAvKOzpgEAvKO2sUNAvKOpuEGAvKOso4OAvKOnqsHAvKO6tAIAvKO9v0BAoO/sMAJArHuxuMMArHu0ogEArHuvrQNArHuitEGArHulv4PArHu4psHArHuzsAIArHu2u0BArHupokJArHusrYCAtz3pNUGAtz3sPIPAtz3nJ8HAtz36MQIAtz39OEBAtz3wI4JAtz3uNcLAtz3rKoCAtz3kJkEAtz3hPwMAvuYl+UBAvuYi7gIAvuYz68CAvuY44IJAvuY29QLoss2yluI0z0nxSF+dZUevTE74mw='
                )
            ));

            if($response->getStatusCode() == 200) {
                $BinDetails = $this->parseResponseBody((string) $response->getBody());
                return $BinDetails;
            }
        } catch(GuzzleHttp\Exception\ServerException $e) {
            $response = $e->getResponse();
            $TechInfo = "Status Code: {$response->getStatusCode()} {$response->getReasonPhrase()}\n";
            $Headers = print_r($response->getHeaders(), true);
            $TechInfo .= "Headers:\n {$Headers}\n\n";
            $Body = (string) $response->getBody();
            $TechInfo .= "Body:\n {$Body}";
            throw new Exception('We got a HTTP Error :( Technical Info: '.base64_encode($TechInfo));
        }
    }

    private function parseResponseBody($Response)
    {
        require_once 'simple_html_dom.php';
        $RootObj = str_get_html($Response);
        if($RootObj->find('div[id=pnlCollectionDetails]') == null) {
            $body = base64_encode($Response);
            $Error = "Could not find the data, it's possible Wigan Council have updated their system\n{$body}";
            throw new Exception($Error);
        } else {
            $BinDetails = array();
            $BinDetails['address'] = $RootObj->find('div[id=pnlCollectionDetails] h3', 0)->plaintext;
            $BinDetails['domestic'] = array(
                'collection-day' => $RootObj->find('div[id=pnlCollectionDetails] p.domestic strong', 1)->plaintext,
                'next-collection' => $RootObj->find('div[id=pnlCollectionDetails] p.domestic strong', 2)->plaintext
            );
            return $BinDetails;
        }
    }

}
