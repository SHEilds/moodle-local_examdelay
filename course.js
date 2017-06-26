var query = "query=getchildren"
getAjax(window.location.origin+'/local/examdelay/requesthandler.ajax.php?'+query, function(data) {
    var children = JSON.parse(data)
    var elements = []
    var listelements = document.getElementsByTagName('li')

    for (var i = 0; i < listelements.length; i++) {
        // If the listed element contains quiz in the class name.
        if (listelements[i].className.indexOf('modtype_quiz') !== -1) {
            elements.push(listelements[i])
            var element = listelements[i].nextElementSibling

            if (element != null && element.className.indexOf('modtype_label')) {
                // Is a label
                var text = element.getElementsByTagName('em')[0]
                var cmid = element.id.split('-')[1]

                query = "query=cmidtoinstance&id="+cmid
                console.log(query)
                getAjax(window.location.origin+'/local/examdelay/requesthandler.ajax.php?'+query, function(data) {

                    console.log(data)

                    var instances = []
                    var instance = (data !== -1) ? data : null;
                    if (data.substr(0, 1) == '[' || data.substr(0, 1) == '{') {
                        var instances = JSON.parse(data)
                    }

                    if (text !== null && text !== undefined && instances.length > 0 && instance != null) {
                        var keys = Object.keys(children)
                        var child = null
                        for (var l = 0; l < keys.length; l++){
                            if (keys[l] === instance) {
                                child = children[keys[l]]
                            }
                        }

                        if (child.time === "false") {
                            element.getElementsByTagName('em')[0].innerHTML = ""
                        } else {
                            element.getElementsByTagName('em')[0].innerHTML = text.innerHTML.replace('%DAYS', child.time)
                        }
                    }
                })
            }
        }
    }

    // var larger = (children.length > elements.length) ? children : elements
    // var using = (larger.length == children.length) ? "children" : "elements"
    // for (var i = 0; i < larger.length; i++) {
    //     if (using === "elements") {
    //         var element = larger[i]

    //         console.log(element.innerHTML)
    //     }
    // }
})

function getAjax(url, success) {
    var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP')
    xhr.open('GET', url)
    xhr.onreadystatechange = function() {
        if (xhr.readyState>3 && xhr.status==200)
            success(xhr.responseText)
    }
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
    xhr.send()
    return xhr
}

function postAjax(url, data, success) {
    var params = typeof data == 'string' ? data : Object.keys(data).map(
            function(k){ return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]) }
        ).join('&')

    var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP")
    xhr.open('POST', url)
    xhr.onreadystatechange = function() {
        if (xhr.readyState>3 && xhr.status==200)
            success(xhr.responseText)
    }
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
    xhr.send(params)
    return xhr
}