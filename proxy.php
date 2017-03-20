  <?php
	// PHP Proxy example for Yahoo! Web services. 
	// Responds to both HTTP GET and POST requests
	//
	// Author: Jason Levitt
	// December 7th, 2005
	//
    //Modified by Allen Howard (amh217@pitt.edu).

    // Cross Site Scripting Allow -- amh217 3/18/17
	//header("Access-Control-Allow-Origin: *");

	// Allowed hostname (api.local and api.travel are also possible here)
	define ('HOSTNAME', 'https://sova.pitt.edu/');

	// Get the REST call path from the AJAX application
	// Is it a POST or a GET?
	$path = ($_GET['sova_link']) ? $_GET['sova_link'] : "";
	$url = ($path != "") ? $path : HOSTNAME;

	// Open the Curl session
	$session = curl_init($url);

	// If it's a POST, put the POST data in the body
	if (@$_POST['sova_link']) {
	    $postvars = '';
	    while ($element = current($_POST)) {
		$postvars .= urlencode(key($_POST)).'='.urlencode($element).'&';
		next($_POST);
	    }
	    curl_setopt ($session, CURLOPT_POST, true);
	    curl_setopt ($session, CURLOPT_POSTFIELDS, $postvars);
	}

	// Don't return HTTP headers. Do return the contents of the call
	curl_setopt($session, CURLOPT_HEADER, false);
	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

    //Ignore SSL Issues: --amh217 3/18/2017: found at http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/
   curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);

    // Make the call
	$xml = curl_exec($session);
    $out = $xml;


    //Checking to see what the headers have- if it's a redirect, successful login must have happened, take to home page through proxy.php, else, continue on as it is going --amh217 3/18/2017  Adapted from: http://stackoverflow.com/questions/2964834/php-check-if-url-redirects  (This code is from line HERE)
    
    // line endings is the wonkiest piece of this whole thing
    $out = str_replace("\r", "", $out);

    // only look at the headers
    $headers_end = strpos($out, "\n\n");
    if( $headers_end !== false ) { 
        $out = substr($out, 0, $headers_end);
    }   

    $headers = explode("\n", $out);
    foreach($headers as $header) {
        if( substr($header, 0, 10) == "Location: " || substr($header, 0, 10) == "location: ") {
            
        } //Their code ends here;
        else{
            // Create a new DOMDocument object
            $dom = new DOMDocument();
            // Parse the HTML from SOVA into the object we created
            @$dom->loadHTML($xml);
            // Create a new XPath object from our DOMObject
            $xpathToLinks = new DOMXPath($dom);
            // Query the XPath object for all "a" nodes in the document
            $nodes = $xpathToLinks->query('/html/body//a');
            // For each 'a' node...
            foreach($nodes as $node) {
                // Get the 'href' attribute
                $origHref = $node->getAttribute('href');
                // URL encode it
                $encoded = urlencode($origHref);
                // Rewrite the link to be our proxy
                $newHref = "proxy.php?sova_link=".$encoded;
                // Remove the original attribute
                $node->removeAttribute('href');
                // Set the new attribute
                $node->setAttribute('href', $newHref);
            }
        }   
    } 


	

    // Echo out the modified HTML dom object
	echo $dom->saveHTML();
	curl_close($session);
?>
