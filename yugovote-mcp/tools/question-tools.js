/**
 * Question Management MCP Tools
 */
import { wp } from '../lib/wp-client.js';

export function registerQuestionTools(server) {

    // ── list_quiz_categories ─────────────────────────
    server.tool(
        'list_quiz_categories',
        'List all available question categories (e.g. History, Geography, Music, Sports). Use these IDs when creating questions or quizzes.',
        {},
        async () => {
            const data = await wp.get('/lookup/quiz-categories');
            return { content: [{ type: 'text', text: JSON.stringify(data, null, 2) }] };
        }
    );

    // ── list_quiz_levels ─────────────────────────────
    server.tool(
        'list_quiz_levels',
        'List all quiz difficulty levels (e.g. Lako/Easy, Srednje/Medium, Teško/Hard). Use these IDs when creating questions or quizzes.',
        {},
        async () => {
            const data = await wp.get('/lookup/quiz-levels');
            return { content: [{ type: 'text', text: JSON.stringify(data, null, 2) }] };
        }
    );

    // ── list_questions ───────────────────────────────
    server.tool(
        'list_questions',
        'List existing questions, optionally filtered by category and/or difficulty level.',
        {
            category_id: { type: 'number', description: 'Filter by question_category term ID.' },
            difficulty_id: { type: 'number', description: 'Filter by difficulty level (quiz_levels post ID).' },
            search: { type: 'string', description: 'Search questions by keyword.' },
            per_page: { type: 'number', description: 'Results per page. Default: 50.' },
            page: { type: 'number', description: 'Page number. Default: 1.' },
        },
        async (params) => {
            const data = await wp.get('/questions', params);
            return { content: [{ type: 'text', text: JSON.stringify(data, null, 2) }] };
        }
    );

    // ── get_question ─────────────────────────────────
    server.tool(
        'get_question',
        'Get full details of a specific question including answers and which one is correct.',
        {
            question_id: { type: 'number', description: 'The question ID to retrieve.' },
        },
        async ({ question_id }) => {
            const data = await wp.get(`/questions/${question_id}`);
            return { content: [{ type: 'text', text: JSON.stringify(data, null, 2) }] };
        }
    );

    // ── create_question ──────────────────────────────
    server.tool(
        'create_question',
        `Create a new quiz question on YugoVote.

⚠️ CRITICAL INSTRUCTIONS FOR THE AGENT:
1. You MUST verify that the correct answer is 100% factually accurate before creating. Do NOT guess.
2. If you are not completely certain about the answer, DO NOT create the question. Instead, tell the user you cannot verify it.
3. All question text and answers must be in SERBIAN language.
4. Topics should cover all former Yugoslavia countries: Serbia, Croatia, Bosnia & Herzegovina, Montenegro, North Macedonia, and Slovenia.
5. The difficulty level must match the actual difficulty of the question.
6. Use verification_notes to document WHY you believe the answer is correct (citing your sources/reasoning).
7. Answers array should have 2-6 possible answers, with exactly one correct_answer index.

Parameters:
- title: Short identifier for the question (used in admin)
- question_text: The actual question text shown to users
- answers: Array of 2-6 answer strings
- correct_answer: Zero-based index of the correct answer in the answers array
- difficulty_id: ID from list_quiz_levels
- category_id: ID from list_quiz_categories
- verification_notes: Your reasoning for why this answer is correct`,
        {
            title: { type: 'string', description: 'Short title/identifier for admin (Serbian).' },
            question_text: { type: 'string', description: 'The full question text shown to users (Serbian).' },
            answers: {
                type: 'array',
                items: { type: 'string' },
                description: 'Array of 2-6 possible answers (Serbian).',
            },
            correct_answer: {
                type: 'number',
                description: 'Zero-based index of the correct answer in the answers array.',
            },
            difficulty_id: { type: 'number', description: 'Difficulty level ID (from list_quiz_levels).' },
            category_id: { type: 'number', description: 'Question category term ID (from list_quiz_categories).' },
            verification_notes: {
                type: 'string',
                description: 'Your reasoning/evidence for why the correct answer is right. REQUIRED for quality assurance.',
            },
        },
        async (params) => {
            const data = await wp.post('/questions', params);
            return { content: [{ type: 'text', text: `Question created successfully!\n\n${JSON.stringify(data, null, 2)}` }] };
        }
    );

    // ── update_question ──────────────────────────────
    server.tool(
        'update_question',
        'Update an existing question. Only provided fields are changed. Same accuracy rules apply as create_question.',
        {
            question_id: { type: 'number', description: 'The question ID to update.' },
            title: { type: 'string', description: 'New title.' },
            question_text: { type: 'string', description: 'New question text.' },
            answers: { type: 'array', items: { type: 'string' }, description: 'New answers array.' },
            correct_answer: { type: 'number', description: 'New correct answer index.' },
            difficulty_id: { type: 'number', description: 'New difficulty level ID.' },
            category_id: { type: 'number', description: 'New category ID.' },
            verification_notes: { type: 'string', description: 'Updated verification reasoning.' },
        },
        async ({ question_id, ...rest }) => {
            const data = await wp.put(`/questions/${question_id}`, rest);
            return { content: [{ type: 'text', text: `Question updated!\n\n${JSON.stringify(data, null, 2)}` }] };
        }
    );
}
