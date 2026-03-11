/**
 * Content Audit MCP Tools
 * Tools for reviewing/auditing existing content for quality, grammar, and accuracy.
 */
import { wp } from '../lib/wp-client.js';

export function registerAuditTools(server) {

    // ── audit_voting_list ────────────────────────────
    server.tool(
        'audit_voting_list',
        `Fetch ALL text content of a voting list and its items for quality review.

Returns the list title, description, and every item's title + short description + full content in a structured format optimized for reviewing:
- Grammar and spelling errors (Serbian language)
- Factual accuracy
- Missing or incomplete descriptions
- Inconsistent formatting
- Text quality issues

After reviewing, use update_voting_list to fix list-level issues, or report item-level issues to the user (items are reused across lists).`,
        {
            list_id: { type: 'number', description: 'The voting list ID to audit.' },
        },
        async ({ list_id }) => {
            const data = await wp.get(`/voting-lists/${list_id}`);

            // Format for easy review
            let audit = `# AUDIT: Voting List #${data.id}\n\n`;
            audit += `## List Details\n`;
            audit += `- **Title:** ${data.title}\n`;
            audit += `- **Status:** ${data.status}\n`;
            audit += `- **Categories:** ${data.categories.map(c => c.name).join(', ') || 'None'}\n`;
            audit += `- **Voting Scale:** ${data.voting_scale}\n`;
            audit += `- **Items:** ${data.item_count}\n\n`;
            audit += `## List Description\n\`\`\`\n${data.description || '(empty)'}\n\`\`\`\n\n`;

            if (data.items && data.items.length > 0) {
                audit += `## Items to Review\n\n`;
                data.items.forEach((item, i) => {
                    audit += `### Item ${i + 1}: ${item.title} (ID: ${item.id})\n`;
                    audit += `- **Status:** ${item.status}\n`;
                    audit += `- **Short Description:** ${item.short_description || '(empty)'}\n`;
                    audit += `- **URL:** ${item.item_url || '(none)'}\n`;
                    audit += `- **Image Source:** ${item.image_source || '(none)'}\n`;
                    audit += `- **Categories:** ${item.categories.map(c => c.name).join(', ') || 'None'}\n`;
                    audit += `- **Full Content:**\n\`\`\`\n${item.content || '(empty)'}\n\`\`\`\n\n`;
                });
            }

            audit += `---\n\nPlease review all text content above for:\n`;
            audit += `1. Serbian grammar and spelling errors\n`;
            audit += `2. Factual inaccuracies\n`;
            audit += `3. Missing descriptions\n`;
            audit += `4. Text quality and consistency\n`;

            return { content: [{ type: 'text', text: audit }] };
        }
    );

    // ── audit_quiz_questions ─────────────────────────
    server.tool(
        'audit_quiz_questions',
        `Fetch all questions associated with a quiz for accuracy and quality review.

For automatic quizzes, fetches questions from the quiz's configured categories and difficulty level.
For manual quizzes, fetches the specifically selected questions.

Review each question for:
- Factual correctness of the marked correct answer
- Appropriate difficulty level
- Serbian grammar and spelling
- Clear and unambiguous question text
- Quality of wrong answer options (they should be plausible but clearly wrong)`,
        {
            quiz_id: { type: 'number', description: 'The quiz ID to audit.' },
            category_id: { type: 'number', description: 'Optional: audit questions from a specific category.' },
            difficulty_id: { type: 'number', description: 'Optional: audit questions of a specific difficulty.' },
            per_page: { type: 'number', description: 'Number of questions to fetch. Default: 50.' },
        },
        async ({ quiz_id, category_id, difficulty_id, per_page }) => {
            // Get the quiz info
            const quiz = await wp.get(`/quizzes/${quiz_id}`);

            let questions = [];

            if (quiz.quiz_type === 'manual' && quiz.selected_questions && quiz.selected_questions.length > 0) {
                // Fetch each selected question
                const promises = quiz.selected_questions.map(qid => wp.get(`/questions/${qid}`).catch(() => null));
                questions = (await Promise.all(promises)).filter(Boolean);
            } else {
                // Fetch from categories
                const params = { per_page: per_page || 50 };
                if (category_id) {
                    params.category_id = category_id;
                } else if (quiz.question_categories && quiz.question_categories.length > 0) {
                    params.category_id = quiz.question_categories[0].id;
                }
                if (difficulty_id) {
                    params.difficulty_id = difficulty_id;
                } else if (quiz.difficulty_id) {
                    params.difficulty_id = quiz.difficulty_id;
                }
                questions = await wp.get('/questions', params);
            }

            // Format for review
            let audit = `# AUDIT: Quiz "${quiz.title}" (ID: ${quiz.id})\n\n`;
            audit += `- **Type:** ${quiz.quiz_type}\n`;
            audit += `- **Mode:** ${quiz.quiz_mode}\n`;
            audit += `- **Difficulty:** ${quiz.difficulty || 'Not set'}\n`;
            audit += `- **Questions per play:** ${quiz.num_questions}\n`;
            audit += `- **Time per question:** ${quiz.time_per_question}s\n`;
            audit += `- **Categories:** ${quiz.question_categories.map(c => c.name).join(', ') || 'None'}\n\n`;

            audit += `## Questions to Review (${questions.length} total)\n\n`;

            questions.forEach((q, i) => {
                const answers = q.answers || [];
                const correctIdx = q.correct_answer;

                audit += `### Q${i + 1}: ${q.title} (ID: ${q.id})\n`;
                audit += `**Question:** ${q.question_text}\n`;
                audit += `**Difficulty:** ${q.difficulty || 'Not set'}\n`;
                audit += `**Categories:** ${q.categories.map(c => c.name).join(', ') || 'None'}\n`;
                audit += `**Answers:**\n`;
                answers.forEach((a, j) => {
                    const marker = j === correctIdx ? '✅' : '❌';
                    audit += `  ${marker} ${j}. ${a}\n`;
                });
                if (q.verification_notes) {
                    audit += `**Verification Notes:** ${q.verification_notes}\n`;
                }
                audit += `\n`;
            });

            audit += `---\n\nPlease verify each question for:\n`;
            audit += `1. Is the ✅ marked answer 100% factually correct?\n`;
            audit += `2. Is the difficulty level appropriate?\n`;
            audit += `3. Are the wrong answers plausible but clearly incorrect?\n`;
            audit += `4. Is the Serbian grammar correct?\n`;
            audit += `5. Is the question text clear and unambiguous?\n`;

            return { content: [{ type: 'text', text: audit }] };
        }
    );

    // ── audit_all_voting_lists ───────────────────────
    server.tool(
        'audit_all_voting_lists',
        `Get a summary of ALL voting lists for a high-level audit. Shows titles, statuses, item counts, and categories to help identify lists that need attention.`,
        {
            status: { type: 'string', description: 'Filter by status. Default: all.' },
        },
        async ({ status }) => {
            const lists = await wp.get('/voting-lists', { status, per_page: 100 });

            let summary = `# Voting Lists Overview (${lists.length} lists)\n\n`;
            summary += `| # | ID | Title | Status | Items | Categories |\n`;
            summary += `|---|-----|-------|--------|-------|------------|\n`;

            lists.forEach((list, i) => {
                const cats = list.categories.map(c => c.name).join(', ') || '-';
                summary += `| ${i + 1} | ${list.id} | ${list.title} | ${list.status} | ${list.item_count} | ${cats} |\n`;
            });

            summary += `\n---\nUse \`audit_voting_list\` with a specific list ID for detailed content review.`;

            return { content: [{ type: 'text', text: summary }] };
        }
    );
}
