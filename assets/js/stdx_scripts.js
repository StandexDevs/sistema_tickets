/**
 * Get a prestored setting
 *
 * @param String name Name of of the setting
 * @returns String The value of the setting | null
 */
function ls_get(name) {
    if (typeof (Storage) !== 'undefined') {
        return localStorage.getItem(name)
    } else {
        window.alert('Please use a modern browser to properly view this template!')
    }
}
/**
 * Store a new settings in the browser
 *
 * @param String name Name of the setting
 * @param String val Value of the setting
 * @returns void
 */
function ls_store(name, val) {
    if (typeof (Storage) !== 'undefined') {
        console.log(`LS estableciendo nombre: ${name} y valor: ${val}`);
        localStorage.setItem(name, val)
    } else {
        window.alert('Please use a modern browser to properly view this template!')
    }
}
function getLanguage() {
    let lang = ls_get('language');
    if ( lang == '') {
        console.log('lenguage vacío, se establece a SPA');
        ls_store('language', 'spa')
    } 
    if ( lang == 'eng') {
        console.log('lenguage ENG, se cambia el lenguage');
        changeLanguage(false);
    }
}
function changeLanguage(cambioDeLS = true) {
    let elems = document.querySelectorAll('[data-lang]');
    Array.from(elems).forEach(function(elem, idx) {
        let str_new = elem.dataset["lang"];
        let str_old = elem.innerHTML;
        elem.innerHTML = str_new;
        elem.dataset["lang"] = str_old;
    });
    let target = document.getElementById('btn-lang');
    if (target.classList.contains('flag-icon-es')) {
        target.classList.remove('flag-icon-es');
        target.classList.add('flag-icon-gb');
    } else {
        if (target.classList.contains('flag-icon-gb')) {
            target.classList.remove('flag-icon-gb');
            target.classList.add('flag-icon-es');
        }
    }
    if (cambioDeLS) {
        let lang = ls_get('language');
        console.log(`el lenguaje es: ${lang}`);
        if ( lang == 'eng') {
            console.log(`Actualmente lenguage: ${lang} se cambia a SPA `);
            ls_store('language', 'spa');
        }
        if ( lang == 'spa' || lang == '' || lang == null) {
            console.log(`Actualmente lenguage: ${lang} se cambia a ENG `);
            ls_store('language', 'eng');
        }
    }
}
async function postData(url = '', ar_data, json_result = true ) {
    if (!(ar_data instanceof FormData)) {
        //throw 'Objeto data no es FormData';
        if ( typeof {} == "object" ) ar_data = to_formData(ar_data);
    }
    // Opciones por defecto estan marcadas con un *
    const response = await fetch(url, {
        method: 'POST', // *GET, POST, PUT, DELETE, etc.
        mode: 'no-cors',
        headers: {
        'Content-Type': 'application/json'
        // 'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: ar_data // body data type must match "Content-Type" header
    });
    let preview = response;
    let json_res = null;
	if ( json_result ) {
		try {
			json_res = preview.json();
			// console.log(json_res);
			return json_res;
		} catch (error) {
			console.log(error);
		}
		return json_res; // parses JSON response into native JavaScript objects
	} else {
		return preview;
	}
}
function to_formData(a_data) {
    let l_form = new FormData();
    for (var key in a_data ){
        l_form.append(key, a_data[key]);
    }
    return l_form;
}
function add_option(select, text, value) {
	let nueva_opcion = create_option(text, value);
	select.appendChild(nueva_opcion);
	return;
}
function create_option(text, value ) {
	let option = document.createElement('option');
	option.text = text;
	option.value = value;
	return option;
}
async function error_alert(message, callback, options) {
    let default_options = {
      position: 'center',
      icon: 'error',
      title: message,
      showConfirmButton: false,
      timer: 1500
    };
    if (options) default_options = Object.assign(default_options, options);
    if (callback) {
      let esperando = await Swal.fire(default_options);
      callback();
    } else {
      Swal.fire(default_options);
    }
}
    
async function success_alert(message, callback, options) {
    let default_options = {
      position: 'center',
      icon: 'success',
      title: message,
      showConfirmButton: false,
      timer: 1500
    };
    if (options) default_options = Object.assign(default_options, options);
    if (callback) {
      let esperando = await Swal.fire(default_options);
      callback();
    } else {
      Swal.fire(default_options);
    }
}
async function info_alert(message, callback, options) {
    let default_options = {
        position: 'center',
        icon: 'info',
        title: message,
        showConfirmButton: false,
        timer: 1500
    };
    if (options) default_options = Object.assign(default_options, options);
        if (callback) {
        let esperando = await Swal.fire(default_options);
        callback();
    } else {
        Swal.fire(default_options);
    }
}
async function confirm_alert(message, callback, options) {
    let default_options = {
        position: 'center',
        icon: 'info',
        title: message,
        showConfirmButton: true,
        showCancelButton: true,
        cancelButtonText: 'Cancelar'
    };
    if (options) default_options = Object.assign(default_options, options);
    return await Swal.fire(default_options);
}


function createElementFromHTML(htmlString) {
    var div = document.createElement('div');
    div.innerHTML = htmlString.trim();
    // Change this to div.childNodes to support multiple top-level nodes.
    return div.firstChild;
}