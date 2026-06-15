# Personal Portfolio with Custom CMS

The goal of this project is to achieve a compromise between a full page builder
approach and a code-only solution. The client requests a set of reusable
sections that require a dedicated implementation that defines how it's rendered
in the public site, how its configuration form renders in the admin CMS site,
and all the necessary behavior and styling. The client then creates pages
composed of these sections in the CMS.

The architecture allows simple granular additions of new section
implementations or modifications of existing section implementations with the
aid of or completely by AI coding assistants. The result is an easily extensible
set of sections based on client's new requirements and complete freedom for the
client to define page structure limited only by what the implemented sections
allow.

Most of the core implementation was designed "manually" - no major decisions
made by any AI assistant. The admin frontend was designed with a hybrid
approach and should be eventually reviewed more carefully and possibly
rewritten to keep it from spiraling into unmanagable AI speghethi mess. The
individual section implementations were designed completely by an AI assistant
and that is the main goal of this project - new section additions or changes
can be fully designed and implemented by an AI assistant without compromising
the stability of the entire codebase.
