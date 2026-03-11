#!/usr/bin/env node

/**
 * YugoVote MCP Server
 * 
 * Enables AI agents to manage quizzes (with verified correct answers),
 * create voting lists, and audit content for grammar/quality on yugovote.com.
 * 
 * Connects to WordPress via REST API using Application Passwords.
 */

import 'dotenv/config';
import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { registerQuizTools } from './tools/quiz-tools.js';
import { registerQuestionTools } from './tools/question-tools.js';
import { registerVotingTools } from './tools/voting-tools.js';
import { registerAuditTools } from './tools/audit-tools.js';

// Validate environment
const required = ['WP_BASE_URL', 'WP_USERNAME', 'WP_APP_PASSWORD'];
const missing = required.filter(k => !process.env[k]);
if (missing.length) {
    console.error(`Missing required env vars: ${missing.join(', ')}`);
    console.error('Copy .env.example to .env and fill in your WordPress credentials.');
    process.exit(1);
}

// Create server
const server = new McpServer({
    name: 'yugovote-mcp',
    version: '1.0.0',
    description: `YugoVote MCP Server — Manage quizzes and voting lists on yugovote.com.
  
This server provides tools to:
• Create quizzes with verified, factually correct questions (Serbian language)
• Manage questions with answer accuracy enforcement
• Create and manage voting lists with items
• Audit existing content for grammar, quality, and accuracy

Content covers all former Yugoslavia countries: Serbia, Croatia, Bosnia & Herzegovina, Montenegro, North Macedonia, and Slovenia.`,
});

// Register all tools
registerQuizTools(server);
registerQuestionTools(server);
registerVotingTools(server);
registerAuditTools(server);

// Start server
async function main() {
    const transport = new StdioServerTransport();
    await server.connect(transport);
    console.error('YugoVote MCP Server running on stdio');
}

main().catch((err) => {
    console.error('Fatal error:', err);
    process.exit(1);
});
