/**
 * Utility functions for formatting data in the timeline viewer
 */

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

export const formatSqlQuery = (payload) => {
    if (!payload || !payload.sql) return null;
    
    // Replace bindings in the SQL query
    let query = payload.sql;
    if (payload.bindings && payload.bindings.length > 0) {
        payload.bindings.forEach((binding) => {
            const value = typeof binding === 'string' ? `'${binding}'` : binding;
            query = query.replace('?', value);
        });
    }
    
    return query.trim();
};
