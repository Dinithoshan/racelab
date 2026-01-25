import React, { useState } from 'react';
import RequestCard from './components/RequestCard';
import { useCopyQuery } from './hooks/useCopyQuery';

/**
 * Main component for displaying timeline requests and events
 */
export default function TimelineViewer({ requests }) {
    const [expandedRequests, setExpandedRequests] = useState(new Set());
    const [selectedEvent, setSelectedEvent] = useState(null);
    const [showFullTrace, setShowFullTrace] = useState(new Set());
    const { copiedEventId, copyQueryToClipboard } = useCopyQuery();

    const toggleRequest = (requestId) => {
        const newExpanded = new Set(expandedRequests);
        if (newExpanded.has(requestId)) {
            newExpanded.delete(requestId);
        } else {
            newExpanded.add(requestId);
        }
        setExpandedRequests(newExpanded);
    };

    const toggleFullTrace = (eventId) => {
        const newSet = new Set(showFullTrace);
        if (newSet.has(eventId)) {
            newSet.delete(eventId);
        } else {
            newSet.add(eventId);
        }
        setShowFullTrace(newSet);
    };

    const handleSelectEvent = (event) => {
        setSelectedEvent(event);
    };

    if (!requests || requests.length === 0) {
        return (
            <div className="text-center py-12 text-[#a0aec0]">
                No timeline events recorded yet. Make some requests to see them here!
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {requests.map((request, index) => (
                <RequestCard
                    key={request.request_id || index}
                    request={request}
                    index={index}
                    isExpanded={expandedRequests.has(request.request_id)}
                    onToggle={toggleRequest}
                    selectedEvent={selectedEvent}
                    onSelectEvent={handleSelectEvent}
                    copiedEventId={copiedEventId}
                    onCopyQuery={copyQueryToClipboard}
                    showFullTrace={showFullTrace}
                    onToggleFullTrace={toggleFullTrace}
                />
            ))}
        </div>
    );
}
