# YugoVote Quiz Generation Agent Context

This document contains all necessary information and instructions for the agent tasked with creating quizzes for the YugoVote platform. Give this to the other agent as context.

## 1. Environment & Connection Information

### SSH Access
- **Host**: `82.25.98.202`
- **Port**: `65002`
- **User**: `u239567293`
- **SSH Command**: `ssh u239567293@82.25.98.202 -p 65002` (or simply `ssh yugovote` if using local aliases)

### Paths
- **Local Application Root**: `/Users/bmarkovic/Documents/Projects/YugoVote`
- **Server Git Repo**: `~/yugovote-theme/`
- **Server Production Theme**: `/home/u239567293/domains/yugovote.com/public_html/wp-content/themes/hello-elementor-child/`

## 2. MCP Server Configuration

Since this agent is running on a different computer, you must first set up the MCP server locally on that new machine:

1. Copy the `yugovote-mcp.zip` file to the new computer and extract it.
2. Open a terminal in the extracted folder and run `npm install` to install dependencies.
3. Configure the agent using the JSON below (ensure you replace `/PATH/TO/EXTRACTED/` with the actual path on the new computer):

```json
{
  "yugovote": {
    "command": "node",
    "args": [
      "/PATH/TO/EXTRACTED/yugovote-mcp/index.js"
    ],
    "env": {
      "WP_BASE_URL": "https://yugovote.com",
      "WP_USERNAME": "bojanmark89",
      "WP_APP_PASSWORD": "WpCks0d4S2En4cWtUrGdNkqJ"
    }
  }
}
```

---

## 3. Quiz Creation Instructions (from SKILL.md)

### Critical Rules
1. **100% answer accuracy** — Never create a question unless completely certain the correct answer is factually accurate. If unsure, skip the question and tell the user.
2. **Serbian language only** — All question text, answers, and descriptions must be in Serbian (Latin script).
3. **Yugoslav scope** — Topics must cover all 6 former Yugoslavia countries: Srbija, Hrvatska, Bosna i Hercegovina, Crna Gora, Severna Makedonija, Slovenija.
4. **Balanced coverage** — Distribute questions across countries. Don't favor one country over others.
5. **Verification notes** — Always provide `verification_notes` explaining WHY the answer is correct.

### Workflow
**Step 1: Discover Available Data**
1. Call `list_quiz_categories` → get category IDs and names
2. Call `list_quiz_levels` → get difficulty level IDs
3. Call `list_questions` (with category filter) → check existing questions to avoid duplicates

**Step 2: Create Questions**
For each question, formulate in natural Serbian, verify factually, calibrate difficulty (Beginner, Intermediate, Advanced, Expert), create plausible distractors, and call `create_question`.

**Step 3: Create the Quiz**
After creating enough questions, call `create_quiz` with title, descriptions, difficulty_id, question_category_ids (for automatic mode), num_questions, time_per_question, token_cost, xp_value.

### Answer Format Reference
The `title` field MUST be the **full question text** (identical to `question_text`).
```json
{
  "title": "Koji je glavni grad Crne Gore?",
  "question_text": "Koji je glavni grad Crne Gore?",
  "answers": ["Nikšić", "Podgorica", "Bar", "Budva"],
  "correct_answer": 1,
  "difficulty_id": 687,
  "category_id": 25,
  "verification_notes": "Podgorica je glavni grad Crne Gore od 1946. Bio poznat kao Titograd do 1992."
}
```
*Note: `correct_answer` is zero-indexed.*

---

## 4. MCP API Reference

### Question Tools
- **`create_question`**: Requires `title`, `question_text`, `answers` (array), `correct_answer` (index), `difficulty_id`, `category_id`, `verification_notes`.
- **`list_questions`**: Filters by `category_id`, `difficulty_id`, `search`, `per_page`, `page`.
- **`update_question`**: Same as create, but requires `question_id`.

### Quiz Tools
- **`create_quiz`**: Requires `title`, `description`. Optional: `quiz_type`, `quiz_mode`, `difficulty_id`, `question_category_ids`, `num_questions`, `time_per_question`, `quiz_category_id` (Quiz categories: Film i TV=217, Sport=215, Muzika=216, Culture Club=220, Trendy/Lifestyle=221).
- **`list_quizzes`**: Filter by `status`, `per_page`.

### Lookup Tools
- **`list_quiz_categories`**: Returns available categories.
- **`list_quiz_levels`**: Returns levels. (Expert=690, Advanced=689, Intermediate=688, Beginner=687)
- **`audit_quiz_questions`**: Audit specific quiz questions.
