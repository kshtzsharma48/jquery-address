<?php 

    class Address {
        
        static function state() {
        	
        	// Returns the base state
            return substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
        }
        
        static function value() {
        	
        	// Returns the state value
            return str_replace(Address::state(), '', $_SERVER['REQUEST_URI']);
        }
        
    }
    
    class Data { 

        function Data($file) { 
        	
        	// Loads a data file and prepares it for usage
            $this->doc = new DOMDocument();
            $this->doc->load($file);
            $this->xp = new DOMXPath($this->doc);
            $this->nodes = $this->xp->query('/data/page');
            $this->node = $this->xp->query('/data/page[@href="' . Address::value() . '"]')->item(0);
        }
        
        function nav() {
            $str = '';
            
            // Prepares the navigation links
            foreach ($this->nodes as $node) {
                $href = $node->getAttribute('href');
                $title = $node->getAttribute('title');
                $str .= '<li><a href="' . Address::state() . $href . '"' 
                    . (Address::value() == $href ? ' class="selected"' : '') . '>' . $title . '</a></li>';
            }
            return trim($str);
        }
        
        function content() {
            $str = '';
            
            // Prepares the content
            if (isset($this->node)) {
                foreach ($this->node->childNodes as $node) {
                    $str .= $this->doc->saveXML($node);
                }
            } else {
                $str .= '<p>Page not found.</p>';
            }
            
            return trim($str);
        }
        
        function title(){
            $str='';
            
            // Prepares the title
            foreach($this->nodes as $node){
                $href = $node->getAttribute('href');
                $title = $node->getAttribute('title');
                $str .= Address::value() == $href ? $title : '';
            }
            return trim($str);
        }
    }

    // Experimental patch for IE
    // if (preg_match('/MSIE\s(?!10)/i', $_SERVER['HTTP_USER_AGENT']) && 
    //     $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] != 'XMLHttpRequest' && 
    //     Address::value() != '/') {
    //     header('Location: ' . Address::state() . '/#' . Address::value());
    //     exit();
    // }
    
    $data = new Data('data.xml');

?>
<!DOCTYPE html> 
<html> 
    <head> 
        <title><?php echo($data->title()); ?></title> 
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <link type="text/css" href="styles.css" rel="stylesheet">
        <script type="text/javascript" src="jquery-1.7.1.min.js"></script>
        <script type="text/javascript" src="jquery.address-1.6.min.js"></script>
        <script type="text/javascript">

            var init = true, 
                state = window.history.pushState !== undefined;
            
            // Handles response
            var handler = function(data) {
                $('title').html($('title', data).html());
                $('.content').html($('.content', data).html());
                $('.page').show();
                $.address.title(/>([^<]*)<\/title/.exec(data)[1]);
            };
            
            $.address.state('<?php echo(Address::state()); ?>').init(function() {

                // Initializes the plugin
                $('.nav a').address();
                
            }).change(function(event) {

                // Selects the proper navigation link
                $('.nav a').each(function() {
                    if ($(this).attr('href') == ($.address.state() + event.path)) {
                        $(this).addClass('selected').focus();
                    } else {
                        $(this).removeClass('selected');
                    }
                });
                
                if (state && init) {
                
                    init = false;
                
                } else {
                
                    // Loads the page content and inserts it into the content area
                    $.ajax({
                        url: $.address.state() + event.path,
                        error: function(XMLHttpRequest, textStatus, errorThrown) {
                            handler(XMLHttpRequest.responseText);
                        },
                        success: function(data, textStatus, XMLHttpRequest) {
                            handler(data);
                        }
                    });
                }

            });

            if (!state) {
            
                // Hides the page during initialization
                document.write('<style type="text/css"> .page { display: none; } </style>');
            }
            
        </script> 
    </head> 
    <body> 
        <div class="page"> 
            <h1>jQuery Address State</h1>
            <ul class="nav"><?php echo($data->nav()); ?></ul>
            <div class="content"><?php echo($data->content()); ?></div>
        </div>
    </body> 
</html>