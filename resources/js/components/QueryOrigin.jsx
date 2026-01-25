import React from 'react';

/**
 * Component for displaying query origin information
 */
export default function QueryOrigin({ origin }) {
    if (!origin) return null;

    if (origin.type === 'laravel_internal') {
        return (
            <div className="mb-3 mt-1">
                <span className="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-[#ed8936]/20 text-[#ed8936] border border-[#ed8936]/50">
                    ⚙️ Laravel Internals
                    {origin.description && (
                        <span className="ml-1.5 font-normal">• {origin.description}</span>
                    )}
                </span>
            </div>
        );
    }

    if (origin.type === 'application' && origin.file) {
        const fileName = origin.file.split('/').pop();
        const shortPath = origin.file.split('/').slice(-2).join('/');
        
        return (
            <div className="mb-3 mt-1 space-y-1">
                <div className="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-[#667eea]/20 text-[#667eea] border border-[#667eea]/50">
                    <span className="font-semibold">📍 Triggered by:</span>
                    <span className="ml-2 font-mono">
                        {shortPath}:{origin.line}
                    </span>
                </div>
                {origin.function && (
                    <div className="text-xs text-[#cbd5e0] ml-1 font-mono">
                        {origin.class && <span className="text-[#a0aec0]">{origin.class.split('\\').pop()}::</span>}
                        <span className="font-semibold">{origin.function}()</span>
                    </div>
                )}
            </div>
        );
    }

    return null;
}
