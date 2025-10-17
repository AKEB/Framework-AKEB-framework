
function togglePassword(inputId) {
	const input = document.getElementById(inputId);
	const icon = document.getElementById(inputId + '-icon');
	
	if (input.type === 'password') {
		input.type = 'text';
		icon.classList.remove('bi-eye');
		icon.classList.add('bi-eye-slash');
	} else {
		input.type = 'password';
		icon.classList.remove('bi-eye-slash');
		icon.classList.add('bi-eye');
	}
}

function showSuccessToast(successText) {
	if (successText == undefined || !successText ||successText.length < 1) {
		return;
	}
	const dateTime = new Date();
	let uid = 'toast-' + dateTime.getTime() + '' + Math.random();
	let html = ' \
	<div class="toast fade" id="' + uid + '" role="alert" aria-live="assertive" aria-atomic="true"> \
		<div class="toast-header text-success"> \
			<strong class="me-auto"><i class="bi bi-exclamation-octagon"></i> Success</strong> \
			<small>'+dateTime.toLocaleDateString("ru")+'</small> \
			<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button> \
		</div> \
		<div class="toast-body"> \
			'+successText+' \
		</div> \
	</div> \
	';
	const toast_container = document.getElementById('toast-container');
	toast_container.innerHTML = toast_container.innerHTML + html;
}

function showErrorToast(errorText) {
	if (errorText == undefined || !errorText ||errorText.length < 1) {
		return;
	}
	const dateTime = new Date();
	let uid = 'toast-' + dateTime.getTime() + '' + Math.random();
	let html = ' \
	<div class="toast fade" id="' + uid + '" role="alert" aria-live="assertive" aria-atomic="true"> \
		<div class="toast-header text-danger"> \
			<strong class="me-auto"><i class="bi bi-x-circle"></i> Error</strong> \
			<small>'+dateTime.toLocaleDateString("ru")+'</small> \
			<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button> \
		</div> \
		<div class="toast-body"> \
			'+errorText+' \
		</div> \
	</div> \
	';
	const toast_container = document.getElementById('toast-container');
	toast_container.innerHTML = toast_container.innerHTML + html;
	return document.getElementById(uid);
}

function validateForms() {
	const forms = document.querySelectorAll('.needs-validation')
	// Loop over them and prevent submission
	Array.from(forms).forEach(form => {
		form.addEventListener('submit', event => {
			if (!form.checkValidity()) {
				event.preventDefault()
				event.stopPropagation()
			}

			form.classList.add('was-validated')
		}, false)
	});
}

function togglePasswordButtons() {
	$('.togglePassword').on('click', function() {
		const inputId = $(this)[0].dataset['inputId'];
		togglePassword(inputId)
	});
}

function showAllToasts() {
	Array.from(document.querySelectorAll('.toast')).forEach(toastNode => new bootstrap.Toast(toastNode).show())
}

function validateEmail(objectId) {
	var email = $('#'+objectId);
	const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	if(!re.test(email.val())){
		email.addClass('is-invalid');
		email.get(0).setCustomValidity("Invalid field.");
		return false;
	} else {
		email.removeClass('is-invalid');
		email.get(0).setCustomValidity("");
		return true;
	}
}

function validateEmailInput(objectId) {
	var email = $('#'+objectId);
	email.on('input', function() {
		const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		if(!re.test(email.val())){
			email.addClass('is-invalid');
			email.get(0).setCustomValidity("Invalid field.");
		} else {
			email.removeClass('is-invalid');
			email.get(0).setCustomValidity("");
		}
	});
}

function validatePasswordAlert(objectId) {
		const password = $('#'+objectId);
		const passwordAlert = password.parent().parent().children('div.alert');
		const passwordAlertUl = passwordAlert.children("ul");
		const requirements = passwordAlertUl.children("li.requirements");
		var leng = null;
		var bigLetter = null;
		var smallLetter = null;
		var num = null;
		var specialChar = null;

		requirements.each(function() {
			if ($(this).hasClass("leng"))
				leng = $(this);
			if ($(this).hasClass("big-letter"))
				bigLetter = $(this);
			if ($(this).hasClass("small-letter"))
				smallLetter = $(this);
			if ($(this).hasClass("num"))
				num = $(this);
			if ($(this).hasClass("special-char"))
				specialChar = $(this);

			$(this).addClass("wrong");
		});
		password.on("focus", () => {
			passwordAlert.removeClass("d-none");
			if (!password.hasClass("is-valid")) {
				password.addClass("is-invalid");
			}
			check()
		});
		password.on("blur", () => {
			passwordAlert.addClass("d-none");
		});

		const check = function() {
			const value = password.val();
			const isLengthValid = value.length >= 8;
			const hasUpperCase = /[A-Z]/.test(value);
			const hasLowerCase = /[a-z]/.test(value);
			const hasNumber = /\d/.test(value);
			const hasSpecialChar = /[!@#$%^&*()\[\]{}\\|;:'",<.>/?`~]/.test(value);

			if (leng != null) {
				leng.toggleClass("good", isLengthValid);
				leng.toggleClass("wrong", !isLengthValid);
			} else isLengthValid = true;
			if (bigLetter != null) {
				bigLetter.toggleClass("good", hasUpperCase);
				bigLetter.toggleClass("wrong", !hasUpperCase);
			} else hasUpperCase = true;
			if (smallLetter != null) {
				smallLetter.toggleClass("good", hasLowerCase);
				smallLetter.toggleClass("wrong", !hasLowerCase);
			} else hasLowerCase = true;
			if (num != null) {
				num.toggleClass("good", hasNumber);
				num.toggleClass("wrong", !hasNumber);
			} else hasNumber = true;
			if (specialChar != null) {
				specialChar.toggleClass("good", hasSpecialChar);
				specialChar.toggleClass("wrong", !hasSpecialChar);
			} else hasSpecialChar = true;

			let isPasswordValid = isLengthValid && hasUpperCase && hasLowerCase && hasNumber && hasSpecialChar;

			if (isPasswordValid) {
				password.removeClass("is-invalid");
				password.addClass("is-valid");
				password.get(0).setCustomValidity("");

				requirements.each(function() {
					$(this).removeClass("wrong");
					$(this).addClass("good");
				});

				passwordAlertUl.removeClass("alert-warning");
				passwordAlertUl.addClass("alert-success");
			} else if (password.attr('required') != 'required' && value == '') {
				password.removeClass("is-invalid");
				password.get(0).setCustomValidity("");
			} else {
				password.removeClass("is-valid");
				password.addClass("is-invalid");
				password.get(0).setCustomValidity("Invalid field.");

				passwordAlertUl.addClass("alert-warning");
				passwordAlertUl.removeClass("alert-success");
			}
		}

		password.on("input", check);
}

function validateEqual(objectId1, objectId2) {
	const input1 = $('#'+objectId1);
	const input2 = $('#'+objectId2);
	if (input1.val() != input2.val()) {
		input2.addClass('is-invalid');
		input2.get(0).setCustomValidity("Invalid field.");
		return false;
	} else {
		input2.removeClass('is-invalid');
		input2.get(0).setCustomValidity("");
		return true;
	}
}

function validateEqualInputs(objectId1, objectId2) {
	const input1 = $('#'+objectId1);
	const input2 = $('#'+objectId2);
	input1.on("input", () => {
		if (input1.val() != input2.val()) {
			input2.addClass('is-invalid');
			input2.get(0).setCustomValidity("Invalid field.");
		} else {
			input2.removeClass('is-invalid');
			input2.get(0).setCustomValidity("");
		}
	});
	input2.on("input", () => {
		if (input1.val() != input2.val()) {
			input2.addClass('is-invalid');
			input2.get(0).setCustomValidity("Invalid field.");
		} else {
			input2.removeClass('is-invalid');
			input2.get(0).setCustomValidity("");
		}
	});
}

function otp_input(input_id, otp_div_id) {
	const main_input = document.querySelector("#"+input_id);
	const inputs = document.querySelectorAll("#"+otp_div_id+" > input");
	inputs[0].addEventListener("paste", function (event) {
		console.log('paste event');
		event.preventDefault();
		const pastedValue = (event.clipboardData || window.clipboardData).getData(
			"text"
		);
		const otpLength = inputs.length;
		for (let i = 0; i < otpLength; i++) {
			if (i < pastedValue.length) {
				inputs[i].value = pastedValue[i];
				inputs[i].removeAttribute("disabled");
				inputs[i].focus;
			} else {
				inputs[i].value = ""; // Clear any remaining inputs
				inputs[i].focus;
			}
		}
		let number = '';
		inputs.forEach((input, index1) => {
			number += input.value;
		});
		main_input.value = number;
	});

	inputs.forEach((input, index1) => {
		input.addEventListener("change", (e) => {
			let value = parseInt(e.target.value);
			if (input.value != "" && input.value.length == 6) {
				main_input.value = input.value;
				let numbers = input.value.split('');
				for (let i = 0; i < numbers.length; i++) {
					if (inputs[i]) {
						inputs[i].value = numbers[i];
					}
				}
				return false;
			}
			let number = '';
			inputs.forEach((input, index1) => {
				number += input.value;
			});
			main_input.value = number;
		});
		input.addEventListener("keydown", (e) => {
			const currentInput = input;
			const nextInput = input.nextElementSibling;
			const prevInput = input.previousElementSibling;

			let arr = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0]
			value = parseInt(e.key);
			if (arr.includes(parseInt(e.key))) {
				e.preventDefault();
				if (currentInput.value.length > 0) {
					currentInput.value = "";
				}
				if (value !== "") {
					currentInput.value = value;
				}
				if (nextInput && value !== "") {
					nextInput.focus();
					// nextInput.focus();
				}
				let number = '';
				inputs.forEach((input, index1) => {
					number += input.value;
				});
				main_input.value = number;
				return false;
			}
			if (e.key === "Backspace") {
				e.preventDefault();
				currentInput.value = "";
				if (prevInput) {
					prevInput.focus();
				}
				let number = '';
				inputs.forEach((input, index1) => {
					number += input.value;
				});
				main_input.value = number;
				return false;
			}
			if (e.key === "Delete") {
				e.preventDefault();
				currentInput.value = "";
				if (nextInput) {
					nextInput.focus();
				}
				let number = '';
				inputs.forEach((input, index1) => {
					number += input.value;
				});
				main_input.value = number;
				return false;
			}
			if (e.key === "ArrowRight") {
				if (nextInput) {
					e.preventDefault();
					nextInput.focus();
				}
				return false
			}
			if (e.key === "ArrowLeft") {
				if (prevInput) {
					e.preventDefault();
					prevInput.focus();
				}
				return false
			}
			return true;
		});
	});
}

function setStoredTheme(theme) {
	localStorage.setItem('theme', theme);
}

function getStoredTheme() {
	return localStorage.getItem('theme');
}

function getPreferredTheme() {
	const storedTheme = getStoredTheme();
	if (storedTheme) {
		return storedTheme;
	}
	return 'auto'
}

function setTheme(theme) {
	if (theme === 'auto') {
		document.documentElement.setAttribute('data-bs-theme', (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'))
	} else {
		document.documentElement.setAttribute('data-bs-theme', theme)
	}
}

function changeThemeColorPrefers() {
	setTheme(getPreferredTheme());
}

Object.assign(DataTable.defaults, {
	"pageLength": 50, // Default entries per page
	"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
	searching: true,
	ordering: true,
	paging: true,
	info: true,
	lengthChange: true,
	responsive: true,
	language: {
		url: '/vendor/akeb/framework/src/lang/dataTable_'+lang+'.json'
	}
});

$(document).ready(function() {
	const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
	changeThemeColorPrefers();
	darkModeMediaQuery.addEventListener('change', changeThemeColorPrefers);
	validateForms();
	togglePasswordButtons();
});

