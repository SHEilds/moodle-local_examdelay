var cmid = null;

var queryString = window.location.search
var rawQueries = queryString.split('&')
for (var i = 0; i < rawQueries.length; i++) {
    if (rawQueries[i].indexOf('update') !== -1) {
        cmid = rawQueries[i].split('=')[1].replace(/\"/g, '')
        isExam()
        break
    }
}

function isExam() {
    var query = "query=exists&id="+cmid

    getAjax(window.location.origin+"/local/examdelay/requesthandler.ajax.php?"+query, function(data) {
        var exam = false

        if (data == "true" || data == "false") {
            switch (data) {
                case "true":
                    exam = true
                    break;
                case "false":
                    exam = false
                    break;
                default:
                    exam = false
                    break;
            }
        }

        getCurrentExam(exam)
    })
}

function getCurrentExam(exam) {
    var query = "query=getCurrentParent&id="+cmid

    getAjax(window.location.origin+"/local/examdelay/requesthandler.ajax.php?"+query, function(data) {
        var currentExam = {}

        if (data.length > 0) {
            currentExam = JSON.parse(data)
        }

        getParents(exam, currentExam)
    })
}

function getParents(exam, currentExam) {
    var query = "query=getparents&target=*"

    getAjax(window.location.origin+"/local/examdelay/requesthandler.ajax.php?"+query, function(data) {
        var parents = []

        if (data.length > 0 && data !== '[]') {
            parents = JSON.parse(data)
        }

        exammod(exam, parents, currentExam)
    })
}

function exammod(isExam, parents, currentExam) {
    var yesselected = isExam ? "selected" : ""
    var noselected = isExam ? "" : "selected"

    var examSettings = document.createElement('fieldset')
    examSettings.id = "examblock"
    examSettings.className = "clearfix"

    var form = "\
        <legend class='ftoggler'>\
            Exam Settings\
        </legend>\
        <div class='fcontainer clearfix'>\
            <div id='fitem_id_exammode' class='fitem fitem_fselect'>\
                <div class='fitemtitle'>\
                    <label for='id_exammode'>Exam Mode</label>\
                </div>\
                <div class='felement fselect'>\
                    <select id='examToggle'>\
                        <option "+ yesselected +">Yes</option>\
                        <option "+ noselected +">No</option>\
                    </select>\
                </div>\
            </div>\
            <div id='fitem_id_parentselect' class='fitem fitem_fselect'>\
                <div class='fitemtitle'>\
                    <label for='id_parentselect'>Exam Parent</label>\
                </div>\
                <div class='felement fselect'>\
                    <select id='parentselect'>"

    if (!isExam) form += "<option value='-1' selected></option>"
    Object.keys(parents).forEach(function(key) {
        var selected = (parents[key].id == currentExam.id) ? "selected" : ""
        form += "<option value='"+parents[key].id+"' "+selected+">" + parents[key].name + "</option>"
    })

    form +=         "</select>\
                    <button id='fitem_id_deleteitem'>Delete</button>\
                </div>\
                <div class='fitemtitle'>\
                    <label for='id_newexam'>New Exam</label>\
                </div>\
                <div id='fitem_id_newexam' class='felement ftext'>\
                    <input id='newExamParent'>\
                    <button id='fitem_id_submitexam'>Submit</button>\
                </div>\
            </div>\
        </div>"

    examSettings.innerHTML = form

    var target = null
    var fieldSets = document.getElementsByTagName('fieldset')
    for (var i = 0; i < fieldSets.length; i++) {
        if (fieldSets[i].className.indexOf('hidden') !== -1)
            target = fieldSets[i]
    }

    document.getElementById('mform1').insertBefore(examSettings, target)

    // Save and return to course.
    document.getElementById('id_submitbutton2').addEventListener('click', function(e) {updateExamDatabase(e)})
    // Save and display.
    document.getElementById('id_submitbutton').addEventListener('click', function(e) {updateExamDatabase(e)})

    document.getElementById('fitem_id_submitexam').addEventListener('click', function(e) {
        e.preventDefault()
        createParent()
    })

    document.getElementById('fitem_id_deleteitem').addEventListener('click', function(e) {
        e.preventDefault()
        deleteParent()
    })
}

function updateExamDatabase(e) {
    e.preventDefault()

    var examState = document.getElementById('examToggle').value
    var exam = (examState == "Yes") ? "true" : "false"
    var parent = document.getElementById('parentselect').value

    if (parent !== -1) {
        var query = {
            query: "update",
            exam: exam,
            id: cmid,
            parent: parent
        }

        postAjax(window.location.origin+"/local/examdelay/requesthandler.ajax.php", query, function(data) {
            console.log(data)
        })
    }

    e.target.form.submit()
}

function deleteParent() {
    var parent = document.getElementById('parentselect').value
    var query = {
        query: "deleteparent",
        id: parent
    }

    postAjax(window.location.origin+"/local/examdelay/requesthandler.ajax.php", query, function(data) {
        console.log(data)
        window.location.reload()
    })
}

function createParent() {
    var name = document.getElementById('newExamParent').value

    if (name.length > 0) {
        var query = {
            query: "createparent",
            name: name
        }

        postAjax(window.location.origin+"/local/examdelay/requesthandler.ajax.php", query, function(data) {
            console.log(data)
            window.location.reload()
        })
    } else {
        alert("Please enter a name for the exam.");
    }
}

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