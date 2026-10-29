document.addEventListener('DOMContentLoaded', function(){
    var inputs = document.querySelectorAll("[data-rsfp-formid]");
    inputs.forEach(function(input){
        input.addEventListener('click', function() {
            RSFormPro.addMoreFiles(this);
        });
    });
});