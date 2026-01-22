import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import '../css/app.css'
import TimelineEvents from './TimelineEvents';

const container = document.getElementById('react-app');

if (container) {
    createRoot(container).render(<TimelineEvents />);
}
