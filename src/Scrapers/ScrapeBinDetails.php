<?php namespace BinCo\Scrapers;

use \GuzzleHttp\Client;

class ScrapeBinDetails {
    
    public function __construct($UPRN, $ASPData=false)
    {
        $this->UPRN = $UPRN;
        $this->ASPData = $ASPData;
        $this->httpclient = new \GuzzleHttp\Client();
    }

    public function getDetails()
    {
        try {
            $form_params = array();
            $form_params['lbAddresses'] = $this->UPRN;
            $form_params['__VIEWSTATE'] = $this->ASPData === false ? require_once 'UPRNViewState.php' : $this->ASPData['ViewState'];
            $form_params['__EVENTVALIDATION'] = $this->ASPData === false ? 
                '/wEWKwK+wYPRCwL++IESAuGVvaUCAsvbsOcMAor0rfsEAor0rfsEAtflk4oOAtfl/7cHAvKOltYLAvKO4vMMAvKOzpgEAvKO2sUNAvKOpuEGAvKOso4OAvKOnqsHAvKO6tAIAvKO9v0BAoO/sMAJArHuxuMMArHu0ogEArHuvrQNArHuitEGArHulv4PArHu4psHArHuzsAIArHu2u0BArHupokJArHusrYCAtz3pNUGAtz3sPIPAtz3nJ8HAtz36MQIAtz39OEBAtz3wI4JAtz3uNcLAtz3rKoCAtz3kJkEAtz3hPwMAvuYl+UBAvuYi7gIAvuYz68CAvuY44IJAvuY29QLoss2yluI0z0nxSF+dZUevTE74mw=' 
                : $this->ASPData['EventValidation'];

            $response = $this->httpclient->request('POST', 'http://kinnear.wigan.gov.uk/bincollections/Default.aspx', array('form_params' => $form_params));

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
            throw new \Exception('We got a HTTP Error :( Technical Info: '.base64_encode($TechInfo));
        }
    }

    private function parseResponseBody($Response)
    {
        require_once 'simple_html_dom.php';
        $RootObj = str_get_html($Response);
        if($RootObj->find('div[id=pnlCollectionDetails]') == null) {
            $body = base64_encode($Response);
            $Error = "Could not find the data, it's possible Wigan Council have updated their system\n{$body}";
            throw new \Exception($Error);
        } else {
            $BinDetails = array();
            $BinDetails['address'] = $RootObj->find('div[id=pnlCollectionDetails] h3', 0)->plaintext;
            $BinTypes = array(
                'domestic',
                'brownbin',
                'garden',
                'paper'
            );
            foreach($BinTypes as $BinType) {
                $BinDetails[$BinType] = $this->parseBin($BinType, $RootObj);
            }
            return $BinDetails;
        }
    }

    private function parseBin($BinType, $RootObj)
    {
        $NCollection = \DateTime::createFromFormat(
            'l, d F Y.',
            $RootObj->find('div[id=pnlCollectionDetails] p.'. $BinType .' strong', 2)->plaintext,
            new \DateTimeZone('Europe/London') // Assuming Wigan Council account for Daylight Savings for this date
        );
        return array(
            'collection-day' => $RootObj->find('div[id=pnlCollectionDetails] p.'. $BinType .' strong', 1)->plaintext,
            'next-collection' => $NCollection->format('Y-m-d'),
        );
    }

}
