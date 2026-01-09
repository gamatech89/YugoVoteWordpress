document.addEventListener("DOMContentLoaded", function () {
 
    /** Handle Quiz Level Image Upload */
    let uploadButton = document.querySelector(".upload_image_button");
    let imageInput = document.getElementById("quiz_level_image");
    let imagePreview = document.getElementById("quiz_level_image_preview");

    if (uploadButton) {
        uploadButton.addEventListener("click", function (e) {
            e.preventDefault();

            let mediaUploader = wp.media({
                title: "Choose Image",
                button: { text: "Select Image" },
                multiple: false
            });

            mediaUploader.on("select", function () {
                let attachment = mediaUploader.state().get("selection").first().toJSON();
                imageInput.value = attachment.url;
                imagePreview.src = attachment.url;
            });

            mediaUploader.open();
        });
    }


});
