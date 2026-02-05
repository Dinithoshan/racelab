import React from 'react';
import { formatTimestamp } from '../utils/formatting';
import EventItem from './EventItem';

/**
 * Component for displaying a single request with its events
 */
export default function RequestCard({ 
    request, 
    index,
    isExpanded,
    onToggle,
    selectedEvent,
    onSelectEvent,
    copiedEventId,
    onCopyQuery,
    showFullTrace,
    onToggleFullTrace,
    dbDialect
}) {
    return (
        <div 
            key={request.request_id || index}
            className="bg-[#2d3748] rounded-lg shadow-xl border border-[#4a5568] overflow-hidden"
        >
            {/* Request Summary Header */}
            <div 
                className="bg-linear-to-r from-[#667eea]/20 to-[#9f7aea]/20 p-4 cursor-pointer hover:from-[#667eea]/30 hover:to-[#9f7aea]/30 transition-all border-b border-[#4a5568]"
                onClick={() => onToggle(request.request_id)}
            >
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <span className="text-2xl text-[#e2e8f0]">
                            {isExpanded ? '▼' : '▶'}
                        </span>
                        <div>
                        <div className="font-mono text-sm">
                            {(() => {
                                const httpRequestEvent = request.events?.find(e => e.type === 'http_request');
                                if (!httpRequestEvent?.decoded_payload?.uri) {
                                    return <span className="text-[#cbd5e0]">{request.request_id}</span>;
                                }

                                const { method, path } = getRequestInfo(httpRequestEvent);
                                return (
                                    <div className="flex items-center gap-2">
                                        <MethodBadge method={method} />
                                        <span
                                            className="text-[#cbd5e0] truncate"
                                            title={path}
                                        >
                                            {path}
                                        </span>
                                    </div>
                                );
                            })()}
                            </div>
                            <div className="text-xs text-[#a0aec0] mt-1">
                                Started: {formatTimestamp(request.started_at)}
                            </div>
                        </div>
                    </div>
                    <div className="flex gap-4 text-sm">
                        <div className="text-center">
                            <div className="font-semibold text-[#667eea]">{request.event_count}</div>
                            <div className="text-xs text-[#a0aec0]">Events</div>
                        </div>
                        <div className="text-center">
                            <div className="font-semibold text-[#9f7aea]">{request.query_count}</div>
                            <div className="text-xs text-[#a0aec0]">Queries</div>
                        </div>
                        <div className="text-center">
                            <div className="font-semibold text-[#ed8936]">
                                {request.total_query_time?.toFixed(2)}ms
                            </div>
                            <div className="text-xs text-[#a0aec0]">Query Time</div>
                        </div>
                        <div className="text-center">
                            <div className="font-semibold text-[#48bb78]">
                                {request.duration?.toFixed(2)}ms
                            </div>
                            <div className="text-xs text-[#a0aec0]">Duration</div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Event Timeline */}
            {isExpanded && (
                <div className="p-4 space-y-1 bg-[#1a202c]">
                    {request.events.map((event, eventIndex) => (
                        <EventItem
                            key={event.id || eventIndex}
                            event={event}
                            index={eventIndex}
                            isSelected={selectedEvent?.id === event.id}
                            onSelect={onSelectEvent}
                            copiedEventId={copiedEventId}
                            onCopyQuery={onCopyQuery}
                            showFullTrace={showFullTrace}
                            onToggleFullTrace={onToggleFullTrace}
                            dbDialect={dbDialect}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

//helper function to get the request method and path from the http request event
function getRequestInfo(httpRequestEvent) {

    const { uri, method = 'GET' } = httpRequestEvent.decoded_payload;

    let path = uri;
    if (uri.startsWith('/')) {
        path = uri;
    }

    try {
        path = new URL(uri).pathname;
    } catch {
        path = uri;
    }

    return { method, path };
}


function MethodBadge({ method }) {
    const styles = {
        GET: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        POST: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        PUT: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        PATCH: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        DELETE: 'bg-red-500/20 text-red-400 border-red-500/30',
    };

    const className =
        styles[method] ??
        'bg-gray-500/20 text-gray-400 border-gray-500/30';

    return (
        <span
            className={`px-2 py-0.5 rounded-md border text-xs font-semibold tracking-wide ${className}`}
        >
            {method}
        </span>
    );
}
