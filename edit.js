function isExam() {
    var result = false

    var xhr = new XMLHttpRequest()
    xhr.addEventListener('load', function() {
        if (this.status == 200) {
            result = JSON.parse(this.responseText)
            console.log(this.responseText)
        }
    })
    xhr.open('GET', window.location.origin + "/local/examdelay/requesthandler.ajax.php?query=exists")
    xhr.send()

    return result
}

if (isExam()) {
    var block = document.getElementsByClassName('questionbankwindow')[0]

    var questionbank = document.createElement('div')
    questionbank.id = "bankblock"
    questionbank.className = "clearfix"

    block.appendChild(questionbank)

    qbank = document.getElementById('bankblock')

    for (var i = 0; i < 2; i++)
        qbank.appendChild(block.firstChild)

    var examEdit = document.createElement('div')
    examEdit.id = "examblock"
    examEdit.className = "clearfix"
    examEdit.innerHTML = "\
        <div class='header'>\
            <div class='title'>\
                <h2>Exam Category</h2>\
            </div>\
        </div>\
        <div class='content'>\
            <div class='container'>\
                <div class='module'>\
                    <div class='bd'>\
                        <div class='box generalbox'>\
                            <div class='choosecategory'>\
                                <div class='singleselect'>\
                                    <label for='examcategory_select'>\
                                        Exam Category:\
                                    </label>\
                                    <select id='examcategory_select' class='select autosubmit singleselect'>\
                                        <option>category1</option>\
                                        <option>category2</option>\
                                    </select>\
                                </div>\
                            </div>\
                        </div>\
                    </div>\
                </div>\
            </div>\
        </div>"

    block.appendChild(examEdit)
}