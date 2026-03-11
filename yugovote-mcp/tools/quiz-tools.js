/**
 * Quiz Management MCP Tools
 */
import { wp } from '../lib/wp-client.js';

export function registerQuizTools(server) {

    // ── list_quizzes ─────────────────────────────────
    server.tool(
        'list_quizzes',
        'List all quizzes on YugoVote with their settings (difficulty, mode, categories, etc). Filterable by status.',
        {
            status: { type: 'string', description: 'Filter by status: publish, pending, draft. Default: all.' },
            per_page: { type: 'number', description: 'Number of results. Default: 50.' },
        },
        async ({ status, per_page }) => {
            const data = await wp.get('/quizzes', { status, per_page });
            return { content: [{ type: 'text', text: JSON.stringify(data, null, 2) }] };
        }
    );

    // ── get_quiz ─────────────────────────────────────
    server.tool(
        'get_quiz',
        'Get full details of a specific quiz including all settings, categories, and linked question IDs.',
        {
            quiz_id: { type: 'number', description: 'The quiz ID to retrieve.' },
        },
        async ({ quiz_id }) => {
            const data = await wp.get(`/quizzes/${quiz_id}`);
            return { content: [{ type: 'text', text: JSON.stringify(data, null, 2) }] };
        }
    );

    // ── create_quiz ──────────────────────────────────
    server.tool(
        'create_quiz',
        `Create a new quiz on YugoVote. The quiz is created with "pending" status for admin review.

IMPORTANT: This platform covers all former Yugoslavia countries (Serbia, Croatia, Bosnia, Montenegro, Macedonia, Slovenia). Quiz content should be in SERBIAN language.

Parameters explained:
- quiz_type: "automatic" pulls random questions from categories, "manual" uses hand-picked question_ids
- quiz_mode: "classic" (standard timed) or "speedtime" (faster pace)
- difficulty_id: ID of a quiz_levels post (use list_quiz_levels to find available levels)
- question_category_ids: array of question_category term IDs to pull questions from`,
        {
            title: { type: 'string', description: 'Quiz title (in Serbian).' },
            description: { type: 'string', description: 'Quiz description (in Serbian).' },
            num_questions: { type: 'number', description: 'Number of questions per play. Default: 10.' },
            time_per_question: { type: 'number', description: 'Seconds per question. Default: 10.' },
            quiz_mode: { type: 'string', description: '"classic" or "speedtime". Default: classic.' },
            quiz_type: { type: 'string', description: '"automatic" or "manual". Default: automatic.' },
            difficulty_id: { type: 'number', description: 'ID of the difficulty level (quiz_levels post).' },
            question_category_ids: {
                type: 'array',
                items: { type: 'number' },
                description: 'Array of question_category term IDs for automatic question selection.',
            },
            question_ids: {
                type: 'array',
                items: { type: 'number' },
                description: 'Array of specific question IDs (for manual quiz type only).',
            },
            token_cost: { type: 'number', description: 'Token cost per attempt. Default: 8.' },
            xp_value: { type: 'number', description: 'Base XP reward. Default: 20.' },
            allow_guest_play: { type: 'boolean', description: 'Allow non-logged-in users to play.' },
            quiz_category_id: { type: 'number', description: 'Quiz category taxonomy term ID.' },
        },
        async (params) => {
            const data = await wp.post('/quizzes', params);
            return { content: [{ type: 'text', text: `Quiz created successfully!\n\n${JSON.stringify(data, null, 2)}` }] };
        }
    );

    // ── update_quiz ──────────────────────────────────
    server.tool(
        'update_quiz',
        'Update an existing quiz. Only provided fields are changed.',
        {
            quiz_id: { type: 'number', description: 'The quiz ID to update.' },
            title: { type: 'string', description: 'New title.' },
            description: { type: 'string', description: 'New description.' },
            status: { type: 'string', description: 'New status: publish, pending, draft.' },
            num_questions: { type: 'number', description: 'New number of questions.' },
            time_per_question: { type: 'number', description: 'New time per question.' },
            quiz_mode: { type: 'string', description: '"classic" or "speedtime".' },
            quiz_type: { type: 'string', description: '"automatic" or "manual".' },
            difficulty_id: { type: 'number', description: 'New difficulty level ID.' },
            question_category_ids: { type: 'array', items: { type: 'number' }, description: 'New category IDs.' },
            question_ids: { type: 'array', items: { type: 'number' }, description: 'New question IDs (manual).' },
            token_cost: { type: 'number', description: 'New token cost.' },
            xp_value: { type: 'number', description: 'New XP value.' },
            allow_guest_play: { type: 'boolean', description: 'Allow guest play.' },
            quiz_category_id: { type: 'number', description: 'Quiz category taxonomy term ID.' },
        },
        async ({ quiz_id, ...rest }) => {
            const data = await wp.put(`/quizzes/${quiz_id}`, rest);
            return { content: [{ type: 'text', text: `Quiz updated!\n\n${JSON.stringify(data, null, 2)}` }] };
        }
    );
}
