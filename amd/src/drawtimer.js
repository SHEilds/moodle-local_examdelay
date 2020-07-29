define(['jquery', 'core/ajax', 'core/notification'], function ($, ajax, notification) {
	var cmidToInstance = async function (cmid) {
		var instance

		var promise = ajax.call([
			{
				methodname: 'local_examdelay_exam_cmid_toinstance',
				args: {},
				fail: notification.exception
			},
		])

		await promise[0].then(function (response) {
			instance = response.instance
		})

		return instance
	}

	var getDelayTime = async function (instance) {
		var time

		var promise = ajax.call([
			{
				methodname: 'local_examdelay_exam_get_time',
				args: { instance: instance },
				fail: notification.exception
			}
		])

		await promise[0].then(function (response) {
			time = response.time
		})

		return time
	}

	var init = async function () {
		var promise = ajax.call([
			{
				methodname: 'local_examdelay_get_children',
				args: {},
				fail: notification.exception
			},
		])

		await promise[0].then(function (response) {
			var quizElements = $('.content li.modtype_quiz')
			var children = JSON.parse(data)

			console.log(children)

			quizElements.each(async function (index) {
				var cmid = this.id.split('-')[1]
				var instance = await cmidToInstance(cmid)

				if (instance != -1) {
					var keys = Object.keys(children)
					var child = null

					for (var i = 0; i < keys.length; i++) {
						if (keys[i] === instance) {
							child = children[keys[i]]
						}
					}

					if (child && child.time) {
						var time = await getDelayTime(instance)

						console.log(time)

						// TODO:- Insert time into course view.
					}
				}
			})
		})
	}

	return { init: init }
})
