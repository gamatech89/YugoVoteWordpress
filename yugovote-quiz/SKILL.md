---
name: yugovote-quiz
description: Create quizzes and questions for YugoVote (yugovote.com). Use when asked to create quiz content, generate questions, or manage quiz categories for the YugoVote platform. Covers all former Yugoslavia countries (Serbia, Croatia, Bosnia, Montenegro, North Macedonia, Slovenia). All content in Serbian. Requires the yugovote MCP server to be connected.
---

# YugoVote Quiz Creator

Create quizzes with verified, factually correct questions for yugovote.com.

## Prerequisites

The `yugovote` MCP server must be connected. It provides tools: `list_quiz_categories`, `list_quiz_levels`, `create_question`, `create_quiz`, `list_questions`, etc.

## Critical Rules

1. **100% answer accuracy** — Never create a question unless completely certain the correct answer is factually accurate. If unsure, skip the question and tell the user.
2. **Serbian language only** — All question text, answers, and descriptions must be in Serbian (Latin script).
3. **Yugoslav scope** — Topics must cover all 6 former Yugoslavia countries: Srbija, Hrvatska, Bosna i Hercegovina, Crna Gora, Severna Makedonija, Slovenija.
4. **Balanced coverage** — Distribute questions across countries. Don't favor one country over others.
5. **Verification notes** — Always provide `verification_notes` explaining WHY the answer is correct.

## Quiz Creation Workflow

### Step 1: Discover Available Data

```
1. Call list_quiz_categories → get category IDs and names
2. Call list_quiz_levels → get difficulty level IDs
3. Call list_questions (with category filter) → check existing questions to avoid duplicates
```

### Step 2: Create Questions

For each question, follow this exact process:

1. **Formulate** — Write the question in natural Serbian
2. **Verify** — Confirm the correct answer is 100% factually accurate
3. **Calibrate difficulty** — Match question difficulty to the appropriate level:
   - **Beginner**: Common knowledge most people would know
   - **Intermediate**: Requires some specific knowledge  
   - **Advanced**: Requires deeper knowledge of the topic
   - **Expert**: Only specialists would know
4. **Create distractors** — Wrong answers must be plausible but clearly incorrect
5. **Call `create_question`** with all required fields

### Step 3: Create the Quiz

After creating enough questions, call `create_quiz` with:
- `title` and `description` (Serbian)
- `quiz_type`: "automatic" (pulls from categories) or "manual" (specific question IDs)
- `quiz_mode`: "classic" or "speedtime"
- `difficulty_id` from Step 1
- `question_category_ids` for automatic mode
- `num_questions`, `time_per_question`, `token_cost`, `xp_value`

Quiz is created with `pending` status for admin review.

## Question Quality Checklist

Before creating each question verify:
- [ ] Question is unambiguous — only ONE valid answer possible
- [ ] Correct answer is verifiable fact, not opinion
- [ ] Wrong answers are plausible but clearly wrong
- [ ] Difficulty level matches the actual difficulty
- [ ] No duplicate of existing questions (check with `list_questions`)
- [ ] Grammar is correct Serbian (Latin script)
- [ ] No country bias — balanced representation

## Answer Format Reference

```json
{
  "title": "Glavni grad Crne Gore",
  "question_text": "Koji je glavni grad Crne Gore?",
  "answers": ["Nikšić", "Podgorica", "Bar", "Budva"],
  "correct_answer": 1,
  "difficulty_id": 687,
  "category_id": 25,
  "verification_notes": "Podgorica je glavni grad Crne Gore od 1946. Bio poznat kao Titograd do 1992."
}
```

Note: `correct_answer` is zero-indexed. In the example above, index 1 = "Podgorica".

## Difficulty & Reward Defaults

| Difficulty | time_per_question | token_cost | xp_value |
|-----------|------------------|-----------|---------|
| Beginner | 15s | 6 | 15 |
| Intermediate | 12s | 8 | 20 |
| Advanced | 10s | 10 | 30 |
| Expert | 8s | 12 | 40 |

## Available Categories Reference

Call `list_quiz_categories` to get current categories. Common ones include: Film, Fudbal, Muzika, Istorija, Geografija, Sport, Heroji, Biznis, Televizija, Narodnjaci.

See [references/api_reference.md](references/api_reference.md) for full MCP tool parameter documentation.
