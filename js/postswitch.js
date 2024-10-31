jQuery(function($) {
    var PW = PW || {};
    PW = function($){
        return {
            postDropdown:function(list, selectedID){
                var value, node;
                for(value in list){
                    var id = list[value].id;
                     node += '<option value="'+id;
                    if(id == selectedID)
                        node += '" selected="selected';
                   node += '">'+list[value].title+'</option>';
                }
                $('#posts-dropdown').html(node);
            },
            ajaxPostDropdown:function(catID, selectedID){
                $.post(
                    ajaxurl,
                    {
                        'action':'get_posts_dropdown_list',
                        'cat_id':catID
                    },
                    function(response){
                        PW.postDropdown(response, selectedID);
                    },
                    'json'
                 );
            },
            redirection:function(postID){
                location.search = "?post="+postID+"&action=edit";
            }
        };
    }(jQuery); 
    //get post ID from url
    var part = location.search.split('=')[1];
    if(!part) return;
    var selectedID = part.split('&')[0];
    //bind option select event
    $('#categories-dropdown').change(function(){
        var catID = $('#categories-dropdown option:selected').val();
        PW.ajaxPostDropdown(catID, selectedID);
    }).trigger("change");
    //init categories dropdown options
    $('#posts-dropdown').change(function(){
        var postID = $('#posts-dropdown option:selected').val();
        PW.redirection(postID);
    });
});