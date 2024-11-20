$(document).ready(function() {
    $('#participant_password_first, #participant_password_second').on('keyup', function() {
        var password1 = $('#participant_password_first').val();
        var password2 = $('#participant_password_second').val();

        if (password1 !== password2 && password2 !== '') {
            $('#password-match-error').text('Les mots de passe ne correspondent pas !');
        } else {
            $('#password-match-error').text('');
        }
    });
});