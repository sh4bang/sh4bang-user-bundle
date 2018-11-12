import PasswordType from "./PasswordType";

$(document).ready(() => {
    $('[data-type="generate-password"]').each((index, inputElement) => {
        new PasswordType($(inputElement));
    });
});
