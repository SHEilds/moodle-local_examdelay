define(['jquery', 'core/ajax', 'core/notification'], function ($, ajax, notification) {
    var _instance

	var getModuleId = function () {
		var searchParams = new URLSearchParams(window.location.search)
		return searchParams.get('update')
	}

	var getInstanceFromCmid = async function (cmid) {
		var instance

		var promise = await ajax.call([
			{
				methodname: 'local_examdelay_exam_cmid_toinstance',
				args: { cmid: cmid },
				fail: notification.exception
			},
        ])

        await promise[0].then(function (response) {
            instance = response.instance
        })

		return instance
	}

	var examExists = async function () {
        var exists;

		var promise = ajax.call([
			{
				methodname: 'local_examdelay_exam_exists',
				args: { instance: _instance },
				fail: notification.exception
			}
        ])

        await promise[0].then(function (response) {
            exists = response.exists
        })

		return exists
	}

	var getExam = async function () {
		var exam

		var promise = ajax.call([
			{
				methodname: 'local_examdelay_get_exam',
				args: { instance: _instance },
				fail: notification.exception
			}
        ])
        
        await promise[0].then(function (response) {
            exam = JSON.parse(response.exam)

            if (exam == "false")
                exam = false
        })

		return exam
	}

	var getExams = async function () {
		var exams

		var promise = ajax.call([
			{
                methodname: 'local_examdelay_get_exams',
                args: {},
				fail: notification.exception
			}
        ])

        await promise[0].then(function (response) {
            exams = JSON.parse(response.exams)
        })
        
        return exams
    }

    var updateExamDatabase = async function (e)
    {
        e.preventDefault()

        var examState = document.getElementById('id_exammode').value
        var parent = document.getElementById('id_parentselect').value
        var isExam = examState == true

        if (parent > -1)
        {
            console.log(_instance, isExam, parent)

            var promise = await ajax.call([
                {
                    methodname: 'local_examdelay_update_exam',
                    args: {
                        instance: _instance,
                        exam: isExam,
                        parent: parent
                    },
                    fail: notification.exception
                }
            ])

            await promise[0].then(function (response) {
                console.log(response)
            })
        }

        e.target.form.submit()
    }

    var createParent = async function ()
    {
        var name = document.getElementById('id_newexam').value
        var isExam = await examExists()
        var parents = await getExams()
        
        // TODO:- Check parent name doesn't already exist.
        if (name.length > 0)
        {
            var promise = ajax.call([
                {
                    methodname: 'local_examdelay_create_exam_parent',
                    args: { name: name },
                    fail: notification.exception
                }
            ])

            await promise[0]
                .then(function (response) { })
                .then(function () {
                    window.setTimeout(function () {
                        recalculateOptions(isExam, parents)
                    }, 500)
                })
        }
    }

    var deleteParent = async function ()
    {
        var parent = document.getElementById('id_parentselect').value
        var isExam = await examExists()
        var parents = await getExams()

        var promise = ajax.call([
            {
                methodname: 'local_examdelay_delete_exam_parent',
                args: { parent: parent },
                fail: notification.exception
            }
        ])

        await promise[0]
            .then(function (response) { })
            .then(function () {
                window.setTimeout(function () {
                    recalculateOptions(isExam, parents)
                }, 500)
            })
    }

    var getFormMarkup = function (isExam, parents, currentExam)
    {
        var yesselected = isExam ? "selected" : ""
	    var noselected = isExam ? "" : "selected"

        // IF using IE.
        if (!!window.document.documentMode)
        {
            return "\
                <legend class='ftoggler'>\
                    <a href='#' class='fheader' role='button' aria-controls='id_examsection' aria-expanded='false'>Exam Delay Settings</a>\
                </legend>\
                <div class='fcontainer clearfix'>\
                    <div id='fitem_id_exammode' class='form-group row fitem'>\
                        <span style='text-align: center;'>Internet Explorer is not supported by this plugin. Please update to a modern browser.</span>\
                    </div>\
                </div>"
        }

        var form = "\
        <legend class='ftoggler'>\
            <a href='#' class='fheader' role='button' aria-controls='id_examsection' aria-expanded='false'>Exam Delay Settings</a>\
        </legend>\
        <div class='fcontainer clearfix'>\
            <div id='fitem_id_exammode' class='form-group row fitem'>\
				<div class='col-md-3'>\
					<span class='float-sm-right text-nowrap'></span>\
                    <label class='col-form-label d-inline' for='id_exammode'>Exam Delay Mode</label>\
                </div>\
                <div class='col-md-9 form-inline felement' data-fieldtype='select'>\
                    <select id='id_exammode' class='custom-select'>\
                        <option value='1' " + yesselected + ">Yes</option>\
                        <option value='0' " + noselected + ">No</option>\
                    </select>\
                </div>\
            </div>\
            <div id='fitem_id_parentselect' class='form-group row fitem'>\
                <div class='col-md-3'>\
					<span class='float-sm-right text-nowrap'></span>\
                    <label class='col-form-label d-inline' for='id_parentselect'>Exam Parent</label>\
                </div>\
                <div class='col-md-9 form-inline felement' data-fieldtype='select'>\
                    <select id='id_parentselect'>"
        
        if (!isExam) form += "<option value='-1' selected></option>"
        if (parents && Object.entries(parents).length > 0)
        {
            console.log("RENDER PARENTS: ")
            console.log(parents)

            console.log("PARENT ENTRIES")
            console.log(Object.entries(parents))

            for (const [id, parent] of Object.entries(parents))
            {
                var selected = (currentExam.hasOwnProperty("id") && parent.id == currentExam.id) ? "selected" : ""
    
                form += "\
                        <option value='" +
                        parent.id +
                        "' " +
                        selected +
                        ">" +
                        parent.name +
                        "</option>"
            }
        }

        form += "\
                    </select>\
                    <button type='button' id='id_deleteitem' class='btn btn-secondary mt-1'>Delete</button>\
                </div>\
            </div>"
        
        form += "\
            <div id='fitem_id_createparent' class='form-group row fitem'>\
                <div class='col-md-3'>\
                    <span class='float-sm-right text-nowrap'></span>\
                    <label class='col-form-label d-inline' for='id_newexam'>New Exam Parent</label>\
                </div>\
                <div class='col-md-9 form-inline felement' data-fieldtype='text'>\
                    <input id='id_newexam' class='form-control' type='text'>\
                    <button type='button' id='id_submitexam' class='btn btn-secondary mt-1'>Create</button>\
                </div>\
            </div>\
        </div>"

        return form
    }

    var recalculateOptions = function (isExam, parents) 
    {
        console.log("recalculating options")

        var $select = $('#id_parentselect')
        var currentlySelected = $select.val()

        // Clear out any current entries.
        $select.html("")

        if (!isExam) {
            var option = document.createElement('option')
            option.selected = (currentlySelected < 0)
            option.value = -1

            $select.append(option)
        }

        for (const [id, parent] of Object.entries(parents))
        {
            var option = document.createElement('option')
            option.selected = (currentlySelected == parent.id) ? "selected" : ""
            option.innerText = parent.name
            option.value = parent.id

            console.log(option)

            $select.append(option)
        }
    }

    var init = async function (instance) {        
        _instance = instance

        console.log("INIT MS: " + _instance)
        
        var isExam = await examExists()
        console.log("IS EXAM: " + isExam)

        var parents = await getExams()
        console.log("PARENTS", parents)

        var exam = await getExam()
        console.log("CURRENT EXAM: " + exam)
        
        var examSettings = document.createElement("fieldset")
        examSettings.id = "examSettings"
        examSettings.className = "clearfix collapsible collapsed"

        var form = getFormMarkup(isExam, parents, exam)
        examSettings.innerHTML = form;

        $('fieldset.collapsible').last().after(examSettings)

        // Attach handlers if not using IE.
        if (!!window.document.documentMode == false)
        {
            console.log("binding default event handlers")

            // Save and return to course.
            document
                .getElementById("id_submitbutton2")
                .addEventListener("click", function (e) {
                    updateExamDatabase(e)
                })
            
            // Save and display.
            document
                .getElementById("id_submitbutton")
                .addEventListener("click", function (e) {
                    updateExamDatabase(e)
                })
            
            console.log("binding new event handlers")
            
            // Create exam parent.            
            $('#id_submitexam').on('click', async function () {
                console.log("Clicked create!")
                await createParent()
            })

            // Delete exam parent.
            $('#id_deleteitem').on('click', async function () {
                console.log("Clicked delete!")
                await deleteParent()
            })
        }
    }

	return { init: init }
})
