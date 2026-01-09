document.addEventListener("DOMContentLoaded", function () {
    let numAnswersField = document.getElementById("num_answers");
    let answerFieldsContainer = document.getElementById("answer_fields");
    let postID = new URLSearchParams(window.location.search).get("post");

    if (!numAnswersField || !answerFieldsContainer) {
        console.error("Required elements not found.");
        return;
    }

    function updateAnswerFields(savedData = {}) {
        let num = parseInt(numAnswersField.value) || 2;
        num = Math.max(2, Math.min(6, num)); // Limit between 2-6

        // ✅ Store existing answers before clearing fields
        let existingAnswers = [];
        let existingCorrectAnswer = null;

        document.querySelectorAll("#answer_fields .cs-quiz-answer-container").forEach((container, index) => {
            let input = container.querySelector("input[type='text']");
            let radio = container.querySelector("input[type='radio']");
            existingAnswers[index] = input ? input.value : "";
            if (radio && radio.checked) {
                existingCorrectAnswer = index;
            }
        });

        answerFieldsContainer.innerHTML = ""; // Clear existing fields

        for (let i = 0; i < num; i++) {
            let answerDiv = document.createElement("div");
            answerDiv.classList.add("cs-quiz-answer-container");

            let inputField = document.createElement("input");
            inputField.type = "text";
            inputField.name = "quiz_answers[]";
            inputField.placeholder = "Enter answer";
            inputField.required = true;
            inputField.style.width = "80%";

            // ✅ Restore Existing Answer if Available
            if (existingAnswers[i]) {
                inputField.value = existingAnswers[i];
            } else if (savedData.answers && savedData.answers[i]) {
                inputField.value = savedData.answers[i];
            }

            let radioBtn = document.createElement("input");
            radioBtn.type = "radio";
            radioBtn.name = "correct_answer";
            radioBtn.value = i;

            // ✅ Restore Correct Answer Selection
            if (existingCorrectAnswer !== null && existingCorrectAnswer === i) {
                radioBtn.checked = true;
            } else if (typeof savedData.correct_answer !== "undefined" && savedData.correct_answer == i) {
                radioBtn.checked = true;
            }

            answerDiv.appendChild(inputField);
            answerDiv.appendChild(radioBtn);
            answerDiv.appendChild(document.createTextNode(" Correct"));

            answerFieldsContainer.appendChild(answerDiv);
        }
    }

    // ✅ Handle new post (no post ID)
    if (!postID) {
        updateAnswerFields();
        numAnswersField.addEventListener("change", () => updateAnswerFields());
        return;
    }

    // ✅ Fetch saved question data via AJAX for existing posts
    fetch(ajaxurl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=get_quiz_question_data&post_id=${postID}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                numAnswersField.value = data.data.num_answers;
                updateAnswerFields(data.data);
            }
        })
        .catch(error => console.error("Error fetching quiz question data:", error));

   // ✅ Preserve answers when changing the number of answers
    numAnswersField.addEventListener("change", () => updateAnswerFields());
});
