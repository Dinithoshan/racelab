import React from 'react';
import { formatSqlQuery } from '../utils/formatting';
import TraceSummary from './TraceSummary';
import QueryOrigin from './QueryOrigin';
import StackTrace from './StackTrace';

/**
 * Component for displaying detailed information about an event
 */
export default function EventDetails({ 
    event, 
    copiedEventId, 
    onCopyQuery,
    showFullTrace,
    onToggleFullTrace 
}) {
    if (!event.decoded_payload && !event.file) {
        return null;
    }

    const isCopied = copiedEventId === event.id;
    const isQuery = event.type === 'query';
    const payload = event.decoded_payload;

    return (
        <div className="mt-2 text-xs space-y-1 w-full">
            {/* Show Laravel internals if present (always show this) */}
            {isQuery && payload?.origin?.type === 'laravel_internal' && (
                <QueryOrigin origin={payload.origin} />
            )}
            
            {/* Show trace summary prominently for query events (from our engine) */}
            {isQuery && event.decoded_trace_summary && (
                <TraceSummary traceSummary={event.decoded_trace_summary} />
            )}
            
            {/* Show query origin for backward compatibility (if not Laravel internals and no trace summary) */}
            {isQuery && payload?.origin && 
             payload.origin.type !== 'laravel_internal' && 
             !event.decoded_trace_summary && (
                <QueryOrigin origin={payload.origin} />
            )}
            
            {event.file && (
                <div className="text-[#cbd5e0]">
                    📄 {event.file}:{event.line}
                </div>
            )}
            {event.class && (
                <div className="text-[#cbd5e0]">
                    🏷️ {event.class}::{event.function}
                </div>
            )}
            {payload && (
                <div className="bg-[#2d3748] p-2 rounded border border-[#4a5568] mt-1 relative max-w-[80%] overflow-hidden">
                    {isQuery && (
                        <div className="flex items-center justify-between mb-2 pb-2 border-b border-[#4a5568]">
                            <span className="font-semibold text-[#e2e8f0]">SQL Query</span>
                            <button
                                onClick={(e) => {
                                    e.stopPropagation(); // Prevent event deselection
                                    onCopyQuery(event, e);
                                }}
                                className={`px-3 py-1 rounded text-xs font-medium transition-all ${
                                    isCopied 
                                        ? 'bg-[#48bb78] text-white' 
                                        : 'bg-[#667eea] text-white hover:bg-[#5568d3]'
                                }`}
                                title="Copy formatted query to clipboard"
                            >
                                {isCopied ? '✓ Copied!' : '📋 Copy Query'}
                            </button>
                        </div>
                    )}
                    {isQuery && payload.sql ? (
                        <>
                            <pre className="whitespace-pre-wrap break-all text-xs font-mono text-[#e2e8f0] select-all w-full">
                                {formatSqlQuery(payload)}
                            </pre>
                            {payload.time_ms && (
                                <div className="mt-2 pt-2 border-t border-[#4a5568] text-[#cbd5e0]">
                                    ⏱️ Execution time: {payload.time_ms}ms
                                </div>
                            )}
                        </>
                    ) : (
                        <pre className="whitespace-pre-wrap break-all text-xs text-[#e2e8f0] w-full">
                            {JSON.stringify(payload, null, 2)}
                        </pre>
                    )}
                </div>
            )}
            
            {/* Show full stack trace for query events */}
            {isQuery && payload?.stack_trace && (
                <StackTrace
                    stackTrace={payload.stack_trace}
                    eventId={event.id}
                    isShowing={showFullTrace.has(event.id)}
                    onToggle={onToggleFullTrace}
                />
            )}
        </div>
    );
}
