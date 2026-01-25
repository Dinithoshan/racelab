import React from 'react';

/**
 * Component for displaying full stack traces with toggle functionality
 */
export default function StackTrace({ stackTrace, eventId, isShowing, onToggle }) {
    if (!stackTrace || !Array.isArray(stackTrace) || stackTrace.length === 0) {
        return null;
    }

    return (
        <div className="mt-3">
            <button
                onClick={(e) => {
                    e.stopPropagation(); // Prevent event deselection
                    onToggle(eventId);
                }}
                className="text-xs text-[#667eea] hover:text-[#5568d3] font-medium mb-2 transition-colors"
            >
                {isShowing ? '▼ Hide' : '▶ Show'} Full Stack Trace ({stackTrace.length} frames)
            </button>
            {isShowing && (
                <div className="bg-[#1a202c] text-[#e2e8f0] p-3 rounded border border-[#4a5568] max-h-96 overflow-y-auto">
                    <div className="space-y-1 font-mono text-xs">
                        {stackTrace.map((frame, index) => {
                            const { file, line, class: className, function: functionName, source, short_path } = frame;
                            const isApplication = source === 'application';
                            
                            return (
                                <div 
                                    key={index}
                                    className={`p-2 rounded ${isApplication ? 'bg-[#48bb78]/20 border border-[#48bb78]/50' : 'bg-[#2d3748] border border-[#4a5568]'}`}
                                >
                                    <div className="flex items-start gap-2">
                                        <span className="text-[#a0aec0] text-xs w-8 shrink-0">#{index}</span>
                                        <div className="flex-1">
                                            {className && functionName ? (
                                                <div className="text-[#667eea]">
                                                    {className}::{functionName}()
                                                </div>
                                            ) : functionName ? (
                                                <div className="text-[#667eea]">{functionName}()</div>
                                            ) : null}
                                            {file && (
                                                <div className="text-[#cbd5e0] mt-1">
                                                    {short_path || file}
                                                    {line && <span className="text-[#ed8936]">:{line}</span>}
                                                </div>
                                            )}
                                            {source && (
                                                <div className="text-[#a0aec0] text-xs mt-1">
                                                    [{source}]
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
