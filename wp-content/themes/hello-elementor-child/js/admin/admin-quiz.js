jQuery(document).ready(function ($) {
    const quizType = $("#quiz_type");
    const automaticOptions = $("#automatic_options");
    const manualOptions = $("#manual_options");
    const categoryFilter = $("#quiz_category_filter");
    const filterBtn = $("#filter_questions");
    const questionSelect = $("#quiz_question_select");
    const addQuestionBtn = $("#add_quiz_question");
    const questionTable = $("#quiz_questions_table tbody");
    const hiddenField = $("#quiz_questions");

    if (!quizType.length || !automaticOptions.length || !manualOptions.length) {
        console.error("Quiz Type elements not found.");
        return;
    }

    // ✅ Handle Quiz Type Change (Automatic vs. Manual)
    quizType.on("change", function () {
        if (quizType.val() === "automatic") {
            automaticOptions.show();
            manualOptions.hide();
        } else {
            automaticOptions.hide();
            manualOptions.show();
        }
    });

    // ✅ Initialize Select2 on Categories and Questions
    $("#quiz_question_categories").select2({ width: "100%", placeholder: "-- Select Categories --" });
    questionSelect.select2({ width: "100%", placeholder: "-- Select a Question --" });

    // ✅ Fetch Questions Based on Category Filter (or all if empty)
    function fetchQuestions(categoryId = "") {
        $.post(ajaxurl, { action: "filter_quiz_questions", category_id: categoryId }, function (data) {
            questionSelect.empty().append('<option value="">-- Select a Question --</option>');
            data.forEach((question) => {
                questionSelect.append(new Option(question.title, question.id, false, false));
            });
            questionSelect.trigger("change");

            // ✅ Restore selected questions after fetching questions
            restoreSelectedQuestions();
        }).fail(function (error) {
            console.error("Error fetching questions:", error);
            alert("Failed to load questions. Please try again.");
        });
    }

    // ✅ Load all questions when page loads
    fetchQuestions();

    // ✅ Handle Category Filter Click
    filterBtn.on("click", function () {
        fetchQuestions(categoryFilter.val());
    });

    // ✅ Restore Selected Questions on Edit
    // function restoreSelectedQuestions() {
    //     let savedQuestions = hiddenField.val();
 
    //     if (savedQuestions) {
    //         let questionIds = JSON.parse(savedQuestions);
    //         questionIds.forEach((id) => {
    //             let title = questionSelect.find(`option[value='${id}']`).text();
             
    //         });
    //     }
    // }

    // ✅ Add Question to Table
    addQuestionBtn.on("click", function () {
        let selectedId = questionSelect.val();
        let selectedText = questionSelect.find("option:selected").text();

        if (!selectedId) return;
        if (questionTable.find(`tr[data-id='${selectedId}']`).length) {
            alert("This question is already added.");
            return;
        }

        addQuestionToTable(selectedId, selectedText);
    });

    function addQuestionToTable(id, title) {
        let row = $(`
            <tr data-id="${id}">
                <td>${title}</td>
                <td><button type="button" class="remove-question button button-small">Remove</button></td>
            </tr>
        `);
        questionTable.append(row);
        updateHiddenField();
    }

    // ✅ Remove Question from Table
    questionTable.on("click", ".remove-question", function () {
        $(this).closest("tr").remove();
        updateHiddenField();
    });

    // ✅ Update Hidden Field to Store Selected Question IDs
    function updateHiddenField() {
        let questionIds = questionTable.find("tr").map(function () {
            return $(this).attr("data-id");
        }).get();

        hiddenField.val(JSON.stringify(questionIds));
    }

    // ✅ Restore Questions on Load
    // restoreSelectedQuestions();
});
