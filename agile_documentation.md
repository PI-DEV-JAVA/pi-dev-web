# Project Agile Documentation

## 1. User Stories & Task Breakdown

| ID US | User Story | TASK ID | Task Name | Priority | Estimation | Status |
|-------|---------------------------------------------------------------------------------------------------------------------------------------|---------|--------------------------------------------------------------------------------------------------------|----------|------------|--------|
| **US-01**| As an HR manager, I want to manage (CRUD) interviews so that I can organize the hiring pipeline. | T.1.1 | Create Interview and Meet entities along with database schema. | High | 2h | Done |
| | | T.1.2 | Build the `InterviewController` CRUD methods (`index`, `new`, `show`, `edit`, `delete`). | High | 3h | Done |
| | | T.1.3 | Create Twig templates for Interview management interfaces. | High | 2h | Done |
| | | T.1.4 | Implement Symfony form validations and UI error handling. | Medium | 1h | Done |
| **US-02**| As a recruiter, I want to add notes and numerical grades to an interview so that I can evaluate candidates properly. | T.2.1 | Add specific note-taking and grading properties to the `Interview` database entity. | High | 1h | Done |
| | | T.2.2 | Add grade layout and text input areas to the recruiter's meeting UI Twig template. | Medium | 1h | Done |
| | | T.2.3 | Handle grading submit logic and safely persist evaluation to the backend. | High | 2h | Done |
| **US-03**| As an interviewer/candidate, I want to join a WebRTC video conference with a collaborative code editor for technical interviews. | T.3.1 | Integrate and configure the Janus SFU WebRTC server connected to the backend. | High | 3h | Done |
| | | T.3.2 | Build custom UI for multi-user WebRTC client-side media rendering and negotiations. | High | 3h | Done |
| | | T.3.3 | Integrate a code editing library (like Monaco or CodeMirror). | High | 2h | Done |
| | | T.3.4 | Synchronize code editor state continuously between all peers using WebSockets. | High | 4h | Done |
| **US-04**| As a candidate, I want to view my scheduled interviews in a calendar view so I don't miss them. | T.4.1 | Install and configure the FullCalendar JS library in the frontend infrastructure. | Medium | 1h | Done |
| | | T.4.2 | Build the candidate dashboard layout supporting the FullCalendar components. | Medium | 2h | Done |
| | | T.4.3 | Implement an AJAX controller endpoint fetching the specific user's planned events. | Medium | 2h | Done |
| | | T.4.4 | Map backend database meeting data to FullCalendar native event rendering format. | Medium | 1h | Done |
| **US-05**| As a candidate, I want my interview to be automatically scheduled when I apply for an offer so the process is seamless. | T.5.1 | Implement `WorkflowEngine` service encapsulating automated selection rules. | High | 3h | Done |
| | | T.5.2 | Hook a Doctrine EventListener to `Application` post-persist actions. | High | 1h | Done |
| | | T.5.3 | Automatically locate an available interview time slot using availability constraints matching. | High | 2h | Done |
| | | T.5.4 | Trigger an alert/email notification to candidates highlighting scheduling confirmation. | Low | 1h | Done |
| **US-06**| As a system admin, I want Interview data exposed via an API so that external applications can integrate with us. | T.6.1 | Configure `#[ApiResource]` attributes on relevant `Interview` & `Meet` entities. | Medium | 1h | Done |
| | | T.6.2 | Prepare proper serialization groups for precise security-limited data exposure. | Medium | 1h | Done |
| | | T.6.3 | Configure token-based API authentication constraints and specific scoping. | High | 2h | Done |

---

## 2. Sequence Diagrams per Feature

### Feature: Job Application Workflow Automation (US-05)
```mermaid
sequenceDiagram
    title Application Workflow Automation
    participant Candidate
    participant WebApp as Application Controller
    participant Workflow as WorkflowEngine Service
    participant Database

    Candidate->>WebApp: Submit Job Application
    WebApp->>Database: Save Application Record
    WebApp->>Workflow: Trigger Workflow for Application
    Workflow->>Workflow: Evaluate rules & find open slots
    Workflow->>Database: Auto-Generate Meeting Schedule
    Database-->>Workflow: Save confirmation
    Workflow-->>WebApp: Complete Process
    WebApp-->>Candidate: Display Success Message
```

### Feature: Video Conference with Collaborative Code Editor (US-03)
```mermaid
sequenceDiagram
    title Technical Interview Room (WebRTC + WebSockets)
    actor Candidate
    actor Recruiter
    participant App as PI-DEV Application
    participant Janus as Janus WebRTC SFU
    participant WS as WebSocket Server

    Note over Candidate, WS: Phase 1: Room Initialization & Media Connection
    Candidate->>App: Open Interview Room URL
    Recruiter->>App: Open Interview Room URL
    
    par WebRTC Audio/Video Connection
        Candidate->>Janus: Join VideoRoom Plugin Payload
        Janus-->>Candidate: Confirm Joined
        Candidate->>Janus: Publish WebRTC Stream (SDP Offer)
        Janus-->>Candidate: SDP Answer
        Janus-->>Recruiter: Event: New Publisher Available
        Recruiter->>Janus: Request Subscription to Candidate
        Janus-->>Recruiter: Forward Candidate Media Stream
    and Collaborative Editor WebSocket Handshake
        Candidate->>WS: Connect to Room Namespace
        WS-->>Candidate: Connection Ack & Current File State
        Recruiter->>WS: Connect to Room Namespace
        WS-->>Recruiter: Connection Ack & Current File State
    end

    Note over Candidate, WS: Phase 2: Collaborative Technical Evaluation
    Candidate->>App: Types character in Code Editor UI
    App->>WS: Emit code diff event via WebSocket
    WS->>Recruiter: Broadcast code diff
    Recruiter->>App: Editor UI updates simultaneously
    Recruiter->>App: Evaluates and writes a secure Note
    App->>App: AJAX POST Note update to internal Backend
```

### Feature: Fetching Scheduled Interviews Calendar (US-04)
```mermaid
sequenceDiagram
    title Candidate Dashboard Calendar
    participant Candidate
    participant CalendarUI as FullCalendar Module
    participant API as Backend Endpoint
    participant Database
    
    Candidate->>CalendarUI: View Calendar Page
    activate CalendarUI
    CalendarUI->>API: AJAX request for events (month date range)
    API->>Database: Query Interviews/Meets for user ID
    Database-->>API: Return query records
    API-->>CalendarUI: Return JSON formatted events
    CalendarUI-->>Candidate: Render Meets on visual calendar
    deactivate CalendarUI
```

---

## 3. Physical Architecture

```mermaid
graph TD
    classDef client fill:#f9f9f9,stroke:#333,stroke-width:2px;
    classDef app fill:#d4e6f1,stroke:#333,stroke-width:2px;
    classDef webrtc fill:#f5b041,stroke:#333,stroke-width:2px;
    classDef database fill:#fcf3cf,stroke:#333,stroke-width:2px;

    Client["fa:fa-desktop Client / Web Browser"]:::client
    Server["fa:fa-server Web Server & App (Nginx + Symfony/PHP)"]:::app
    WebSocket["fa:fa-exchange WebSocket Server (NodeJS)"]:::app
    Janus["fa:fa-video-camera WebRTC Server (Janus SFU)"]:::webrtc
    Database[/"fa:fa-database Database (MariaDB/MySQL)"/]:::database

    %% Connections
    Client -- "HTTPS: Web views & API Calls" --> Server
    Client -- "WSS: Collaborative Editor Sync" --> WebSocket
    Client -- "WebRTC/WSS: Media Streams & Signaling" --> Janus
    Server -- "TCP: Read/Write Transactions" --> Database
    Server -- "REST API: Room creation & Control" --> Janus
```
