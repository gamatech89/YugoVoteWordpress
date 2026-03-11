/**
 * Voting List & Item Management MCP Tools
 */
import { wp } from '../lib/wp-client.js';

export function registerVotingTools(server) {

    // ── list_voting_categories ───────────────────────
    server.tool(
        'list_voting_categories',
        'List all voting list categories. Use these IDs when creating voting lists.',
        {},
        async () => {
            const data = await wp.get('/lookup/voting-categories');
            return { content: [{ type: 'text', text: JSON.stringify(data, null, 2) }] };
        }
    );

    // ── list_voting_item_categories ──────────────────
    server.tool(
        'list_voting_item_categories',
        'List all voting item categories. Use these IDs when creating voting items.',
        {},
        async () => {
            const data = await wp.get('/lookup/voting-item-categories');
            return { content: [{ type: 'text', text: JSON.stringify(data, null, 2) }] };
        }
    );

    // ── list_voting_lists ────────────────────────────
    server.tool(
        'list_voting_lists',
        'List all voting lists with metadata (title, status, item count, categories).',
        {
            status: { type: 'string', description: 'Filter by status: publish, pending, draft.' },
            category_id: { type: 'number', description: 'Filter by voting_list_category term ID.' },
            search: { type: 'string', description: 'Search by keyword.' },
            per_page: { type: 'number', description: 'Results per page. Default: 50.' },
            page: { type: 'number', description: 'Page number. Default: 1.' },
        },
        async (params) => {
            const data = await wp.get('/voting-lists', params);
            return { content: [{ type: 'text', text: JSON.stringify(data, null, 2) }] };
        }
    );

    // ── get_voting_list ──────────────────────────────
    server.tool(
        'get_voting_list',
        'Get a specific voting list with all its items, descriptions, and full details.',
        {
            list_id: { type: 'number', description: 'The voting list ID to retrieve.' },
        },
        async ({ list_id }) => {
            const data = await wp.get(`/voting-lists/${list_id}`);
            return { content: [{ type: 'text', text: JSON.stringify(data, null, 2) }] };
        }
    );

    // ── create_voting_list ───────────────────────────
    server.tool(
        'create_voting_list',
        `Create a new voting list on YugoVote. The list is created with "pending" status for admin review.

INSTRUCTIONS:
- Title and description must be in SERBIAN language.
- Topics should cover former Yugoslavia countries: Serbia, Croatia, Bosnia & Herzegovina, Montenegro, North Macedonia, Slovenia.
- voting_scale determines how many items the list contains (5 or 10).
- You must provide item_ids — use list_voting_items or create_voting_item to get IDs first.`,
        {
            title: { type: 'string', description: 'List title (Serbian).' },
            description: { type: 'string', description: 'List description (Serbian).' },
            category_id: { type: 'number', description: 'Voting list category term ID.' },
            voting_scale: { type: 'number', description: 'Number of items: 5 or 10.' },
            item_ids: {
                type: 'array',
                items: { type: 'number' },
                description: 'Array of voting_items post IDs to include in this list.',
            },
            is_featured: { type: 'boolean', description: 'Mark this list as featured.' },
        },
        async (params) => {
            const data = await wp.post('/voting-lists', params);
            return { content: [{ type: 'text', text: `Voting list created!\n\n${JSON.stringify(data, null, 2)}` }] };
        }
    );

    // ── update_voting_list ───────────────────────────
    server.tool(
        'update_voting_list',
        'Update an existing voting list. Only provided fields are changed.',
        {
            list_id: { type: 'number', description: 'The voting list ID to update.' },
            title: { type: 'string', description: 'New title.' },
            description: { type: 'string', description: 'New description.' },
            status: { type: 'string', description: 'New status: publish, pending, draft.' },
            category_id: { type: 'number', description: 'New category ID.' },
            voting_scale: { type: 'number', description: 'New voting scale.' },
            item_ids: { type: 'array', items: { type: 'number' }, description: 'New item IDs.' },
            is_featured: { type: 'boolean', description: 'Featured status.' },
        },
        async ({ list_id, ...rest }) => {
            const data = await wp.put(`/voting-lists/${list_id}`, rest);
            return { content: [{ type: 'text', text: `Voting list updated!\n\n${JSON.stringify(data, null, 2)}` }] };
        }
    );

    // ── list_voting_items ────────────────────────────
    server.tool(
        'list_voting_items',
        'Search and browse existing voting items. Use this to find items to add to a voting list.',
        {
            search: { type: 'string', description: 'Search by keyword.' },
            category_id: { type: 'number', description: 'Filter by voting_item_category term ID.' },
            per_page: { type: 'number', description: 'Results per page. Default: 50.' },
            page: { type: 'number', description: 'Page number. Default: 1.' },
        },
        async (params) => {
            const data = await wp.get('/voting-items', params);
            return { content: [{ type: 'text', text: JSON.stringify(data, null, 2) }] };
        }
    );

    // ── get_voting_item ──────────────────────────────
    server.tool(
        'get_voting_item',
        'Get full details of a specific voting item.',
        {
            item_id: { type: 'number', description: 'The voting item ID.' },
        },
        async ({ item_id }) => {
            const data = await wp.get(`/voting-items/${item_id}`);
            return { content: [{ type: 'text', text: JSON.stringify(data, null, 2) }] };
        }
    );

    // ── create_voting_item ───────────────────────────
    server.tool(
        'create_voting_item',
        `Create a new voting item that can be added to voting lists.

INSTRUCTIONS:
- Title and descriptions must be in SERBIAN language.
- short_description is shown on the voting card (keep it concise).
- content is the full description (shown on the item detail page).`,
        {
            title: { type: 'string', description: 'Item title (Serbian).' },
            short_description: { type: 'string', description: 'Short description for voting card (Serbian).' },
            content: { type: 'string', description: 'Full description / content (Serbian).' },
            item_url: { type: 'string', description: 'Related URL (e.g. YouTube video).' },
            image_source: { type: 'string', description: 'Image source / credit text.' },
            category_id: { type: 'number', description: 'Voting item category term ID.' },
        },
        async (params) => {
            const data = await wp.post('/voting-items', params);
            return { content: [{ type: 'text', text: `Voting item created!\n\n${JSON.stringify(data, null, 2)}` }] };
        }
    );
}
