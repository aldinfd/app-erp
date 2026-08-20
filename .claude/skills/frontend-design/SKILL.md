---
name: frontend-design
description: Guidance for distinctive, intentional visual design when building new UI or reshaping an existing one. Helps with aesthetic direction, typography, and making choices that don't read as templated defaults.
license: Complete terms in LICENSE.txt
---

# Frontend Design

Approach this as the design lead at a small studio known for giving every client a visual identity that could not be mistaken for anyone else's. This client has already rejected proposals that felt templated, and is paying for a distinctive point of view: make deliberate, opinionated choices about palette, typography, and layout that are specific to this brief, and take one real aesthetic risk you can justify.

## Ground it in the subject

If the brief does not pin down what the product or subject is, pin it yourself before designing: name one concrete subject, its audience, and the page's single job, and state your choice. If there's any information in your memory about the human's preferences, context about what they're building, or designs you've made before – use that as a hint. The subject's own world, its materials, instruments, artifacts, and vernacular, is where distinctive choices come from. Build with the brief's real content and subject matter throughout.

## Design principles

For web designs, the hero is a thesis. Open with the most characteristic thing in the subject's world, in whatever form makes sense for it: a headline, an image, an animation, a live demo, an interactive moment. Be deliberate with your choice: a big number with a small label, supporting stats, and a gradient accent is the template answer, only use if that's truly the best option.

Typography carries the personality of the page. Pair the display and body faces deliberately, not the same families you would reach for on any other project, and set a clear type scale with intentional weights, widths, and spacing. Make the type treatment itself a memorable part of the design, not a neutral delivery vehicle for the content.

Structure is information. Structural devices, numbering, eyebrows, dividers, labels, should encode something true about the content, not decorate it. Many generic designs use numbered markers (01 / 02 / 03), but that's only appropriate if the content actually is a sequence - like a real process or a typed timeline where order carries information the reader needs. Question if choices like numbered markers actually make sense before incorporating them.

Leverage motion deliberately. Think about where and if animation can serve the subject: a page-load sequence, a scroll-triggered reveal, hover micro-interactions, ambient atmosphere. An orchestrated moment usually lands harder than scattered effects; choose what the direction calls for. However, sometimes less is more, and extra animation contributes to the feeling that the design is AI-generated.

Match complexity to the vision. Maximalist directions need elaborate execution; minimal directions need precision in spacing, type, and detail. Elegance is executing the chosen vision well.

Consider written content carefully. Often a design brief may not contain real content, and it's up to you to come up with copy. Copy can make a design feel as templated as the design itself. See the below section on writing for more guidance.

## Process: brainstorm, explore, plan, critique, build, critique again

For calibration: AI-generated design right now clusters around three looks: (1) a warm cream background (near #F4F1EA) with a high-contrast serif display and a terracotta accent; (2) a near-black background with a single bright acid-green or vermilion accent; (3) a broadsheet-style layout with hairline rules, zero border-radius, and dense newspaper-like columns. All three are legitimate for some briefs, but they are defaults rather than choices, and they appear regardless of subject. Where the brief pins down a visual direction, follow it exactly — the brief's own words always win, including when it asks for one of these looks. Where it leaves an axis free, don't spend that freedom on one of these defaults. Just like a human designer who's hired, there's often a careful balance between doing what you're good at and taking each project as a chance to experiment and learn.

Work in two passes. First, brainstorm a short design plan based on the human's design brief: create a compact token system with color, type, layout, and signature. Color: describe the palette as 4–6 named hex values. Type: the typefaces for 2+ roles (a characterful display face that's used with restraint, a complementary body face, and a utility face for captions or data if needed). Layout: a layout concept, using one-sentence prose descriptions and ASCII wireframes to ideate and compare. Signature: the single unique element this page will be remembered by that embodies the brief in an appropriate way.

Then review that plan against the brief before building: if any part of it reads like the generic default you would produce for any similar page (work through a similar prompt to see if you arrive somewhere similar) rather than a choice made for this specific brief — revise that part, say what you changed and why. Only after you've confirmed the relative uniqueness of your design plan should you start to write the code, following the revised plan exactly and deriving every color and type decision from it.

When writing the code, be careful of structuring your CSS selector specificities. It's easy to generate CSS classes that cancel each other out (especially with a type-based selector like .section and a element-based selector like .cta). This can happen often with paddings/margins between sections.

Try to do a lot of this planning and iteration in your thinking, and only show ideas to the user when you have higher confidence it'll delight them.

## Restraint and self-critique

Spend your boldness in one place. Let the signature element be the one memorable thing, keep everything around it quiet and disciplined, and cut any decoration that does not serve the brief. Not taking a risk can be a risk itself! Build to a quality floor without announcing it: responsive down to mobile, visible keyboard focus, reduced motion respected. Critique your own work as you build, taking screenshots if your environment supports it – a picture is worth 1000 tokens. Consider Chanel's advice: before leaving the house, take a look in the mirror and remove one accessory. Human creators have memory and always try to do something new, so if you have a space to quickly jot down notes about what you've tried, it can help you in future passes.

## More on writing in design

Words appear in a design for one reason: to make it easier to understand, and therefore easier to use. They are design material, not decoration. Bring the same intentionality to copy that you would bring to spacing and color. Before writing anything, ask what the design needs to say, and how it can best be said to help the person navigate the experience.

Write from the end user's side of the screen. Name things by what people control and recognize, never by how the system is built. A person manages notifications, not webhook config. Describe what something does in plain terms rather than selling it. Being specific is always better than being clever.

Use active voice as default. A control should say exactly what happens when it's used: "Save changes," not "Submit." An action keeps the same name through the whole flow, so the button that says "Publish" produces a toast that says "Published." The vocabulary of an interface is the signposting for someone navigating the product. Cohesion and consistency are how people learn their way around.

Treat failure and emptiness as moments for direction, not mood. Explain what went wrong and how to fix it, in the interface's voice rather than a person's. Errors don't apologize, and they are never vague about what happened. An empty screen is an invitation to act.

Keep the register conversational and tuned: plain verbs, sentence case, no filler, with tone matched to the brand and the audience. Let each element do exactly one job. A label labels, an example demonstrates, and nothing quietly does double duty.

## System status visibility

Don't leave users staring at a blank screen, guessing whether the app is working or frozen. Every action—a click, a submission, an upload—must be acknowledged within milliseconds with a sign that the system is listening: a spinner, a progress bar, or a subtle state change. Silence comes at a cost; uncertain users will double-click, and that’s how bugs begin.

## Real-world language, not system jargon

Write labels, messages, and icons as if explaining them to a friend, not a compiler. "Save failed; connection lost" is far more human than "Error 500." Stick to metaphors users already know—shopping carts, folders, back buttons—and don't invent new terms just because they sound unique.

## Always provide an escape route

Any significant action needs a way to cancel it. Undo, cancel, or return to the previous step—these aren't luxury features; they are safety nets that give users the confidence to experiment. Users who feel trapped will close the tab rather than stick around to find a solution.

## Consistency that frees, not constrains

A "Save" button should behave the same way throughout the app, with predictable placement and color. Consistency isn't about being boring; it frees users from having to relearn the interface every time they open a new screen. Follow platform conventions before creating your own.

## Prevent errors before they happen

Validate input in real-time, disable buttons that aren't ready to be pressed, and ask for confirmation before irreversible actions. The most elegant error is the one that never occurs—don't rely on error messages to patch up sloppy design choices made at the start.

## Show, don't make them remember

Users shouldn't have to memorize product codes, date formats, or the fourth step of an eight-step process. Present selectable options rather than blank fields that demand perfect recall. Recognition is always less taxing than recall. Efficiency for the seasoned, simplicity for the newcomer

Provide shortcuts, quick filters, or bulk actions for power users—but ensure these features don't hinder new users who simply want to complete a single, straightforward task. Good design accommodates both paces simultaneously without making either group feel neglected.

## Disciplined restraint

Every element on the screen must justify its existence. Colors, icons, or dividers that convey no information are merely visual noise that delays users from their goals. Before adding a new element, ask yourself: does this help, or is it just decoration?

## Errors as conversation, not punishment

When something fails, don't throw a stack trace in the user's face. Explain the issue calmly, then indicate the next steps—retry, contact support, or return to a safe state. A good error message reassures and guides rather than assigning blame.

## Help that appears without being intrusive

Documentation and tooltips should be available right where they are needed, not buried on a separate FAQ page. The best help appears the moment confusion arises and vanishes once the task is done—never clinging or disrupting the workflow.

## Responsiveness, accessibility, and speed as the baseline

Build these in as standard: layouts that remain tidy on small screens, clearly visible keyboard focus, animations that respect "reduced motion" preferences, and performance that doesn't keep users waiting. These aren't flashy extras to show off—they are the minimum standards required before something can truly be considered finished.

## Test, refine, polish

Testing and refactoring aren't optional final steps; they are how a project stays healthy as it grows. Critique your code just as you would a design: step back, review it, and have the courage to trim away parts that add complexity without adding value.