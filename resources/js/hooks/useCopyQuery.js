import { useState } from 'react';
import { formatSqlQuery } from '../utils/formatting';

/**
 * Custom hook for handling query copying to clipboard
 */
export function useCopyQuery(dbDialect = 'mysql') {
    const [copiedEventId, setCopiedEventId] = useState(null);

    const copyQueryToClipboard = async (event, e) => {
        e.stopPropagation(); // Prevent event selection toggle
        
        if (event.type !== 'query' || !event.decoded_payload) return;
        
        const formattedQuery = formatSqlQuery(event.decoded_payload, dbDialect);
        if (!formattedQuery) return;
        
        try {
            await navigator.clipboard.writeText(formattedQuery);
            setCopiedEventId(event.id);
            setTimeout(() => setCopiedEventId(null), 2000);
        } catch (err) {
            console.error('Failed to copy query:', err);
        }
    };

    return { copiedEventId, copyQueryToClipboard };
}
