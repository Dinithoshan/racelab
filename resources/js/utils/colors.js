/**
 * Color utilities for event types
 */

export const getEventColor = (type) => {
    const colors = {
        'http_request': 'bg-[#667eea]/20 border-[#667eea]',
        'http_response': 'bg-[#48bb78]/20 border-[#48bb78]',
        'query': 'bg-[#9f7aea]/20 border-[#9f7aea]',
        'stack': 'bg-[#4a5568]/20 border-[#4a5568]',
        'controller': 'bg-[#ed8936]/20 border-[#ed8936]',
        'model_event': 'bg-[#667eea]/20 border-[#667eea]',
        'exception': 'bg-[#f56565]/20 border-[#f56565]',
        'cache': 'bg-[#38b2ac]/20 border-[#38b2ac]',
        'job': 'bg-[#ed8936]/20 border-[#ed8936]',
        'command': 'bg-[#ed64a6]/20 border-[#ed64a6]',
    };
    return colors[type] || 'bg-[#4a5568]/20 border-[#4a5568]';
};

export const getSourceColors = () => ({
    'application': 'bg-[#48bb78]/20 text-[#48bb78] border-[#48bb78]/50',
    'vendor': 'bg-[#ed8936]/20 text-[#ed8936] border-[#ed8936]/50',
    'framework': 'bg-[#ed8936]/20 text-[#ed8936] border-[#ed8936]/50',
    'unknown': 'bg-[#4a5568]/20 text-[#cbd5e0] border-[#4a5568]/50',
});

export const getSourceLabels = () => ({
    'application': 'Application Code',
    'vendor': 'Third-party Package',
    'framework': 'Laravel Framework',
    'unknown': 'Unknown',
});
