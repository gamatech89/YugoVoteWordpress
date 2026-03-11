# YugoVote MCP Tool Reference

## Question Tools

### create_question
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| title | string | Yes | Short admin identifier (Serbian) |
| question_text | string | Yes | Full question shown to users (Serbian) |
| answers | string[] | Yes | 2-6 possible answers (Serbian) |
| correct_answer | number | Yes | Zero-based index of correct answer |
| difficulty_id | number | Yes | From `list_quiz_levels` |
| category_id | number | Yes | From `list_quiz_categories` |
| verification_notes | string | Yes | Reasoning for correctness |

### list_questions
| Parameter | Type | Description |
|-----------|------|-------------|
| category_id | number | Filter by category |
| difficulty_id | number | Filter by difficulty |
| search | string | Keyword search |
| per_page | number | Results per page (default: 50) |
| page | number | Page number |

### update_question
Same parameters as `create_question` plus `question_id` (required). Only provided fields are changed.

## Quiz Tools

### create_quiz
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| title | string | Yes | Quiz title (Serbian) |
| description | string | Yes | Quiz description (Serbian) |
| quiz_type | string | No | "automatic" (default) or "manual" |
| quiz_mode | string | No | "classic" (default) or "speedtime" |
| num_questions | number | No | Questions per play (default: 10) |
| time_per_question | number | No | Seconds per question (default: 10) |
| difficulty_id | number | No | Difficulty level ID |
| question_category_ids | number[] | No | Categories for automatic question selection |
| question_ids | number[] | No | Specific question IDs (manual mode) |
| token_cost | number | No | Token cost per attempt (default: 8) |
| xp_value | number | No | Base XP reward (default: 20) |
| allow_guest_play | boolean | No | Allow non-logged-in users |
| quiz_category_id | number | No | Quiz category taxonomy |

### list_quizzes
| Parameter | Type | Description |
|-----------|------|-------------|
| status | string | publish, pending, draft |
| per_page | number | Results per page |

## Lookup Tools

### list_quiz_categories
No parameters. Returns `[{id, name, slug, count}]`.

### list_quiz_levels
No parameters. Returns `[{id, title}]`. Current levels: Expert (690), Advanced (689), Intermediate (688), Beginner (687).

## Audit Tools

### audit_quiz_questions
| Parameter | Type | Description |
|-----------|------|-------------|
| quiz_id | number | Quiz to audit |
| category_id | number | Optional category filter |
| difficulty_id | number | Optional difficulty filter |
| per_page | number | Number of questions to fetch |
