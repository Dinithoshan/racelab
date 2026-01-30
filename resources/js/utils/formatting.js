/**
 * Utility functions for formatting data in the timeline viewer
 */

import { format } from 'sql-formatter';

export const formatTimestamp = (timestamp) => {
    const date = new Date(timestamp * 1000);
    return date.toLocaleTimeString('en-US', { 
        hour12: false, 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit',
        fractionalSecondDigits: 3 
    });
};

export const formatSqlQuery = (payload, dialect = 'mysql') => {
    if (!payload || !payload.sql) return null;
    
    // Replace bindings in the SQL query
    let query = payload.sql;
    if (payload.bindings && payload.bindings.length > 0) {
        payload.bindings.forEach((binding) => {
            // Handle different binding types
            let value;
            if (binding === null || binding === undefined) {
                value = 'NULL';
            } else if (typeof binding === 'string') {
                // Escape single quotes in strings
                const escaped = binding.replace(/'/g, "''");
                value = `'${escaped}'`;
            } else if (typeof binding === 'boolean') {
                value = binding ? '1' : '0';
            } else {
                value = String(binding);
            }
            query = query.replace('?', value);
        });
    }
    
    // Format the SQL query using sql-formatter
    try {
        return format(query.trim(), {
            language: dialect,
            tabWidth: 2,
            keywordCase: 'upper',
            functionCase: 'upper',
            identifierCase: 'lower',
            dataTypeCase: 'upper',
        });
    } catch (error) {
        // If formatting fails, return the unformatted query
        console.warn('SQL formatting error:', error);
        return query.trim();
    }
};
