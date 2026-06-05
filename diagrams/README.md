# Architecture Diagrams

This folder contains PlantUML diagrams that describe the high-level structure of the MiniTube project.

- `deployment_diagram.puml`: shows the browser, PHP web server/runtime, MVC layers, MySQL database, seed data, and data collector.
- `class_diagram.puml`: summarizes the main classes and relationships across Core, Controller, Service, Repository, Database, Model, Session, and Security layers.
- `sequence_watch_request.puml`: explains a typical `GET /watch.php?video_id=...` request from the client through app routing, controller, services, repositories, database, and view rendering.

These diagrams are intentionally high level so they can be used in the project report without exposing every method in every file.
