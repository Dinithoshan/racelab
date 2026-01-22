import React, { useState } from 'react';

export default function TimelineViewer({ requests }) {
    const [expandedRequests, setExpandedRequests] = useState(new Set());
    const [selectedEvent, setSelectedEvent] = useState(null);
    const [copiedEventId, setCopiedEventId] = useState(null);

    const toggleRequest = (requestId) => {
        const newExpanded = new Set(expandedRequests);
        if (newExpanded.has(requestId)) {
            newExpanded.delete(requestId);
        } else {
            newExpanded.add(requestId);
        }
        setExpandedRequests(newExpanded);
    };

    const getEventIcon = (type) => {
        const icons = {
            'http_request': '🌐',
            'http_response': '✅',
            'query': '🗄️',
            'stack': '📚',
            'controller': '🎯',
            'model_event': '📦',
            'exception': '❌',
            'cache': '💾',
            'job': '⚙️',
            'command': '⌨️',
        };
        return icons[type] || '📌';
    };

    const getEventColor = (type) => {
        const colors = {
            'http_request': 'bg-blue-100 border-blue-300',
            'http_response': 'bg-green-100 border-green-300',
            'query': 'bg-purple-100 border-purple-300',
            'stack': 'bg-gray-100 border-gray-300',
            'controller': 'bg-yellow-100 border-yellow-300',
            'model_event': 'bg-indigo-100 border-indigo-300',
            'exception': 'bg-red-100 border-red-300',
            'cache': 'bg-teal-100 border-teal-300',
            'job': 'bg-orange-100 border-orange-300',
            'command': 'bg-pink-100 border-pink-300',
        };
        return colors[type] || 'bg-gray-100 border-gray-300';
    };

    const formatTimestamp = (timestamp) => {
        const date = new Date(timestamp * 1000);
        return date.toLocaleTimeString('en-US', { 
            hour12: false, 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit',
            fractionalSecondDigits: 3 
        });
    };

    const formatSqlQuery = (payload) => {
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

    const copyQueryToClipboard = async (event, e) => {
        e.stopPropagation(); // Prevent event selection toggle
        
        if (event.type !== 'query' || !event.decoded_payload) return;
        
        const formattedQuery = formatSqlQuery(event.decoded_payload);
        if (!formattedQuery) return;
        
        try {
            await navigator.clipboard.writeText(formattedQuery);
            setCopiedEventId(event.id);
            setTimeout(() => setCopiedEventId(null), 2000); // Reset after 2 seconds
        } catch (err) {
            console.error('Failed to copy query:', err);
        }
    };

    const renderQueryOrigin = (origin) => {
        if (!origin) return null;

        if (origin.type === 'laravel_internal') {
            return (
                <div className="mb-3 mt-1">
                    <span className="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-orange-100 text-orange-800 border border-orange-300">
                        ⚙️ Laravel Internals
                        {origin.description && (
                            <span className="ml-1.5 text-orange-700 font-normal">• {origin.description}</span>
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
                    <div className="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-50 text-blue-900 border border-blue-300">
                        <span className="font-semibold">📍 Triggered by:</span>
                        <span className="ml-2 font-mono text-blue-700">
                            {shortPath}:{origin.line}
                        </span>
                    </div>
                    {origin.function && (
                        <div className="text-xs text-gray-700 ml-1 font-mono">
                            {origin.class && <span className="text-gray-600">{origin.class.split('\\').pop()}::</span>}
                            <span className="font-semibold">{origin.function}()</span>
                        </div>
                    )}
                </div>
            );
        }

        return null;
    };

    const renderEventDetails = (event) => {
        if (!event.decoded_payload && !event.file) {
            return null;
        }

        const isCopied = copiedEventId === event.id;
        const isQuery = event.type === 'query';

        return (
            <div className="mt-2 text-xs space-y-1">
                {/* Show query origin prominently for query events */}
                {isQuery && event.decoded_payload?.origin && renderQueryOrigin(event.decoded_payload.origin)}
                
                {event.file && (
                    <div className="text-gray-600">
                        📄 {event.file}:{event.line}
                    </div>
                )}
                {event.class && (
                    <div className="text-gray-600">
                        🏷️ {event.class}::{event.function}
                    </div>
                )}
                {event.decoded_payload && (
                    <div className="bg-gray-50 p-2 rounded border border-gray-200 mt-1 relative">
                        {isQuery && (
                            <div className="flex items-center justify-between mb-2 pb-2 border-b border-gray-300">
                                <span className="font-semibold text-gray-700">SQL Query</span>
                                <button
                                    onClick={(e) => copyQueryToClipboard(event, e)}
                                    className={`px-3 py-1 rounded text-xs font-medium transition-all ${
                                        isCopied 
                                            ? 'bg-green-500 text-white' 
                                            : 'bg-blue-500 text-white hover:bg-blue-600'
                                    }`}
                                    title="Copy formatted query to clipboard"
                                >
                                    {isCopied ? '✓ Copied!' : '📋 Copy Query'}
                                </button>
                            </div>
                        )}
                        {isQuery && event.decoded_payload.sql ? (
                            <>
                                <pre className="whitespace-pre-wrap text-xs font-mono text-gray-800 select-all">
                                    {formatSqlQuery(event.decoded_payload)}
                                </pre>
                                {event.decoded_payload.time_ms && (
                                    <div className="mt-2 pt-2 border-t border-gray-300 text-gray-600">
                                        ⏱️ Execution time: {event.decoded_payload.time_ms}ms
                                    </div>
                                )}
                            </>
                        ) : (
                            <pre className="whitespace-pre-wrap text-xs">
                                {JSON.stringify(event.decoded_payload, null, 2)}
                            </pre>
                        )}
                    </div>
                )}
            </div>
        );
    };

    const renderEvent = (event, index) => {
        const isSelected = selectedEvent?.id === event.id;
        
        return (
            <div 
                key={event.id || index}
                className={`border-l-4 pl-4 py-2 cursor-pointer transition-all ${getEventColor(event.type)} ${
                    isSelected ? 'ring-2 ring-blue-400' : ''
                }`}
                onClick={() => setSelectedEvent(isSelected ? null : event)}
            >
                <div className="flex items-start justify-between">
                    <div className="flex-1">
                        <div className="flex items-center gap-2">
                            <span className="text-lg">{getEventIcon(event.type)}</span>
                            <span className="font-semibold text-sm">{event.type}</span>
                            {event.elapsed_time && (
                                <span className="text-xs text-gray-500">
                                    +{event.elapsed_time?.toFixed(2)}ms
                                </span>
                            )}
                        </div>
                        {isSelected && renderEventDetails(event)}
                    </div>
                    <div className="text-xs text-gray-500">
                        {formatTimestamp(event.occurred_at)}
                    </div>
                </div>
            </div>
        );
    };

    if (!requests || requests.length === 0) {
        return (
            <div className="text-center py-12 text-gray-500">
                No timeline events recorded yet. Make some requests to see them here!
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {requests.map((request, index) => {
                const isExpanded = expandedRequests.has(request.request_id);
                
                return (
                    <div 
                        key={request.request_id || index}
                        className="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden"
                    >
                        {/* Request Summary Header */}
                        <div 
                            className="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 cursor-pointer hover:bg-blue-100 transition-colors"
                            onClick={() => toggleRequest(request.request_id)}
                        >
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <span className="text-2xl">
                                        {isExpanded ? '▼' : '▶'}
                                    </span>
                                    <div>
                                        <div className="font-mono text-sm text-gray-600">
                                            {request.request_id}
                                        </div>
                                        <div className="text-xs text-gray-500 mt-1">
                                            Started: {formatTimestamp(request.started_at)}
                                        </div>
                                    </div>
                                </div>
                                <div className="flex gap-4 text-sm">
                                    <div className="text-center">
                                        <div className="font-semibold text-blue-600">{request.event_count}</div>
                                        <div className="text-xs text-gray-500">Events</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="font-semibold text-purple-600">{request.query_count}</div>
                                        <div className="text-xs text-gray-500">Queries</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="font-semibold text-orange-600">
                                            {request.total_query_time?.toFixed(2)}ms
                                        </div>
                                        <div className="text-xs text-gray-500">Query Time</div>
                                    </div>
                                    <div className="text-center">
                                        <div className="font-semibold text-green-600">
                                            {request.duration?.toFixed(2)}ms
                                        </div>
                                        <div className="text-xs text-gray-500">Duration</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Event Timeline */}
                        {isExpanded && (
                            <div className="p-4 space-y-1 bg-gray-50">
                                {request.events.map((event, eventIndex) => 
                                    renderEvent(event, eventIndex)
                                )}
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
