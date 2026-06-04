var dialog;
/*
    SELECT USER DATA
*/

let selectUserHtml = 
`<div class="wp-editor-help">
    ${userSelect.html}
    
    <label>
        <input type="checkbox" id="insert-picture">
        Show user picture
    </label>
    <br>
    <label>
        <input type="checkbox" id="insert-phonenumbers">
        Show user phonenumbers
    </label>
    <br>
    <label>
        <input type="checkbox" id="insert-email">
        Show user e-mail
    </label>
</div>`;

let selectUserDialog = {
    width: 350,
    height: 160,
    title: 'Insert user data',
    items: {
        type: 'container',
        classes: 'wp-help',
        html: selectUserHtml
    },
    buttons: [{
        text: 'Insert link',
        onclick: function(){
            var userId = document.querySelector('.wp-editor-help [name="user-selection"]').value;
            if(userId != ''){
                var options = '';
                if(document.getElementById('insert-picture').checked){
                    options += ' picture=true';
                }
                if(document.getElementById('insert-phonenumbers').checked){
                    options += ' phone=true';
                }
                if(document.getElementById('insert-email').checked){
                    options += ' email=true';
                }
                tinymce.activeEditor.insertContent(`[user-link id="${userId}"${options}]`);
            }
            dialog.close();
        }
    },{
        text: 'Close',
        onclick: 'close'
    }]
} ;

//select user
tinymce.create(
    //fileupload
    'tinymce.plugins.select_user',
    {
        init:function(editor, url){    
            editor.addCommand('mceSelect_user',
                function(){
                    dialog                             = editor.windowManager.open(selectUserDialog);
                    let select                        = document.querySelector('.wp-editor-help [name="user-selection"]');
                    Main.attachNiceSelect(select);
                    let niceSelect                     = select._niceSelect.dropdown
                    niceSelect.style.position        = 'relative';
                    niceSelect.style.width            = "200px";
                    niceSelect.style.border            = '2px solid #303030';
                    niceSelect.style.marginBottom    = '10px';
                }
            );
            
            editor.addButton('select_user',
                {
                    tooltip: 'Insert userinfo like name or phone',
                    title:'Insert userinfo',
                    cmd:'mceSelect_user',
                    image:url+'/../pictures/usericon.png'
                }
            );
        
        },
        
        createControl:function(){
            return null
        },
    }
);

//Register the plugin
tinymce.PluginManager.add(
    'select_user',
    tinymce.plugins.select_user
)

console.log('Frontend posting tiny_mce.js loaded');