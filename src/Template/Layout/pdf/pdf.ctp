<!DOCTYPE html>
<html>
<head>
    
    <title>
        <?= $this->fetch('title') ?>
    </title>
    <style type="text/css">
    	/*@font-face {
	      font-family: 'DroidSans Fallback';
	      font-style: normal;
	      font-weight: 400;
	      src: url(/fonts/DroidSansFallback.ttf) format('truetype');
	    }*/
	    html,body {
	    	padding: 0 !important;
	    }
    	body {
    		font-family:  sans-serif;
    		font-size: 10pt;
    	}
    	p {
    		margin-bottom: 5px;
    		margin-top: 5px;
    	}
    </style>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body>
    <div class="container py-4">
        <?= $this->fetch('content') ?>
    </div>
</body>
</html>
