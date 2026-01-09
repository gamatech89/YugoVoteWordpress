<?php


function custom_admin_menu_styles() {
    echo '<style>
 


        #menu-posts-quiz,#menu-posts-quiz_levels,#menu-posts-question {
            background: #002d4b !important;
        }
        
        #menu-posts-quiz:hover,#menu-posts-quiz_levels:hover,#menu-posts-question:hover {
            background: #002d4b !important;
        }
        
        #menu-posts-quiz {
            padding-top:8px !important;
        }
        #menu-posts-question {
            padding-bottom:8px !important;
        }

        #menu-posts-quiz::before {
            content:"";
            width:100%;
            height:3px;
            background:red;
            display:flex;
            top:0;
            position:absolute;
            
        }
              #menu-posts-question::before {
            content:"";
            width:100%;
            height:3px;
            background:red;
            display:flex;
            margin-top:6px;
            bottom:0;
            position:absolute;
            
        }
    </style>';
}
add_action('admin_head', 'custom_admin_menu_styles');