var files = { 
    ppt: [], 
    pdf: []
}; 

jQuery(document).ready(function($) {

    $('div.creator_content button#submit').on('click', function(e){
        var tags = get_all_tags(all_tag_divs);
        const request = new XMLHttpRequest();
        
        request.onload = () => {
            let responseObject = null;
            try{
                responseObject = JSON.parse(request.responseText);
            } catch (e){
                console.error('Could not parse JSON!');
            }
            if(responseObject){
                console.log(responseObject);
                if(!responseObject.status){
                   $(".status_error").empty();

                   $.each(responseObject.message, function(index, value){
                       var li = document.createElement('li');
                       li.textContent = value;
                       li.classList.add('error');
                       $('.status_error').append(li);
                   });
                }
                else{
                    window.location.href="http://www.big-bio.org/beta/dashboard/";
                }
            }
        }

        var formData = new FormData();
        formData.append('action','ajaxmodsubmit');
        formData.append('title', $('#title').val());
        formData.append('content', $('#content').val());
        formData.append('sup_notes', $('#sup-notes').val());
        formData.append('ack', $('#acknowledge').val());
        formData.append('security',$('#security').val());
        formData.append('collab', tags[0]);
        formData.append('doi', tags[1]);
        formData.append('keyterms', tags[2]);
        formData.append('presentation', files['ppt'][0]);
        formData.append('worksheet', files['pdf'][0]);
        request.open('POST', '/beta/wp-admin/admin-ajax.php' , true);
        request.send(formData);
    });

    var all_tag_divs = ['collab-cont', 'doi-cont', 'key-cont'];
    var all_tag_placeholders = { 
        'collab-cont': "Jane Goddall", 
        'doi-cont': "10.1172/JCI25421",
        'key-cont': "New Key Term"
    };

    all_tag_divs.forEach(function(element){
        setup_input(document.getElementById(element), element);
    });

    function get_all_tags(arr){
        var total = [];
        arr.forEach((e) => {
            var box = [];
            var children = document.getElementById(e).children[0].children;
            for(let item of children){
                box.push(item.textContent);
            }
            total.push(box);
        });
        return total;
    }

    function setup_input(container, type){
        var all_tags = document.createElement('div');
        all_tags.classList.add('tags');
        container.appendChild(all_tags);
        var input = document.createElement('input');
        input.type = "text";
        input.style = "margin-top: 15px;";
        input.placeholder = all_tag_placeholders[type];

        input.onkeypress = function(e){
            if(!e) e = window.event;
            var keyCode = e.keyCode || e.which;
            if(keyCode == '13'){
                if(input.value != ""){
                    add_tag(all_tags, input.value);
                    input.value = "";
                }
            }
        }
        container.appendChild(input);
    }

    function add_tag(container, input, type){
        var temp = document.createElement('DIV');
        var close = document.createElement('SPAN');
        close.classList.add('close');

        temp.classList.add('tag');

        temp.innerHTML = input;
        close.addEventListener('click', function(e){
            temp.remove();
        });
        container.appendChild(temp);
        container.children[container.children.length - 1].appendChild(close);
    }

    function doi_tag(container, input){
        let example = new Cite('Q21972834');

        let output = example.format('bibliography', {
          format: 'html',
          template: 'apa',
          lang: 'en-US'
        })

        console.log(output);
    }  
});

function add_file(e,_this){ 
    // if no file selected don't do anything
    // ppt and pdf files must be stored separately (localstorage?)
    var id = _this.id;
    var container = _this.previousElementSibling;
    var input = _this.value;

    var i = files[id].length;

    files[id].push(_this.files[0]);


    var temp = document.createElement('DIV');
    temp.setAttribute('data', "index: '-1'");
    temp.dataset.index = i;
    var close = document.createElement('SPAN');
    close.classList.add('close');
    temp.classList.add('tag');

    temp.innerHTML = input.substring(input.lastIndexOf('\\')+1);
    close.addEventListener('click', function(e){
        delete files[id][temp.dataset.index];
        temp.remove();
        files[id] = files[id].filter(Boolean);
        console.log(files);

        var x = document.getElementById(id);
        x.style.display = '';
        var y = document.getElementsByTagName('LABEL');
        for(var i =0; i< y.length; i++){
            if(y[i].htmlFor == id){
                y[i].style.display = '';
            }
        }
    });

    container.appendChild(temp);
    container.children[container.children.length - 1].appendChild(close);
    console.log(files['ppt']);


    var x = document.getElementById(id);
    x.style.display = 'none';
    var y = document.getElementsByTagName('LABEL');
    for(var i =0; i< y.length; i++){
        if(y[i].htmlFor == id){
            y[i].style.display = 'none';
        }
    }
}





