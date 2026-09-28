# The Health Hub — editorial guide

The Together Clinic edition of the "I should have done this sooner" framework.
Read this before writing, reviewing or publishing anything for the Hub.

## 1. What the Hub is for

A magazine, not a blog. Think of the Slimming World magazine: it sells a life, not a
product. Food, confidence, small wins, the everyday bits of changing your weight.

The Hub has two jobs:

1. **Draw people in.** Warm, useful stories that someone thinking about their weight
   wants to read and share, and that search engines and AI answers want to quote.
2. **Keep patients going.** Most of a patient's journey happens between orders.
   Stories that help them eat well on a smaller appetite, get through a plateau or
   handle a holiday keep them on track. That is where most of the Hub's value is.

Fewer, better pieces. Every story is reviewed by a pharmacist, carries a real
photograph, and leaves the reader with something practical.

## 2. Voice

Lead with the feeling the reader already has, not with the product or the clinic.
The feeling already exists; the story meets them in it.

| Theme | What it answers | Where it lives |
|---|---|---|
| I should have done this sooner | Delay after the decision | Closes, confirmation moments |
| Nobody told me this was an option | Not knowing where to start | Awareness pieces |
| I didn't realise it could be this easy | Assumed complexity | Practical how-tos |
| I just needed someone to explain it | Information overload | Clear explainers |
| I was making it harder than it needed to be | Overthinking, all-or-nothing | Mind & motivation, Move more |
| I wish someone had pointed me here years ago | A long history of trying | Real life |

**Structure of every story**

1. **Opening:** a moment the reader recognises. Second person, concrete, short.
2. **Body:** clear, factual, useful. Headed sections, lists where they help. No
   emotional framing in the facts: the moment feeling bleeds into clinical detail,
   trust breaks.
3. **Close:** return to the feeling. Frame the next step as the natural one.

**Words to avoid** (they read as AI-written): genuinely, straightforward, honestly,
simply, just, exactly, completely, actually, probably. If a sentence sounds kind or
reassuring, ask whether a real person would say it out loud. If not, rewrite it.

British English throughout.

## 3. Sections

| Section | Slug | What goes in it |
|---|---|---|
| Eat well | `eat-well` | Food that tastes good, fills you up and doesn't cost the earth |
| Move more | `move-more` | Activity that fits real lives; strength; starting small |
| Mind & motivation | `mind` | Habits, setbacks, plateaus, being kind to yourself |
| Real life | `real-life` | Holidays, birthdays, eating out, family, busy weeks |
| Your treatment | `your-treatment` | Practical help while on treatment, from our pharmacists |

Each story goes in **one** section (its WordPress category). The sections are created
automatically; don't rename their slugs.

## 4. Compliance — non-negotiable

Together Clinic is a pharmacy. UK law does not allow prescription-only medicines to be
advertised to the public, and the ASA, MHRA and GPhC treat weight-loss medicines as a
priority. A Hub story is marketing in their eyes, so:

- **No medicine names** in titles, standfirsts, section names, image alt text or
  social posts. In the body, only in *Your treatment* pieces where it's clinically
  necessary, stated factually, and signed off by a pharmacist.
- **No weight-loss figures or outcome claims.** No "lose X%", "up to", "in weeks",
  before-and-afters or comparisons with other treatments.
- **No invented people.** No testimonials, patient quotes or stories presented as real.
  A real patient's story needs their written consent, and must be about their life,
  not a medicine's effect.
- **No unsupported claims** such as "most patients tell us…". If we can't evidence it,
  don't say it.
- **No medicine imagery.** No packs, pens or pills identifying a product. Stories
  without a photograph show a designed cover in their section's colours.
- **Facts from UK sources** (NHS, FSA, NICE), stated so they can be checked.
- **Safety-netting** in anything touching on treatment or symptoms: when to contact us,
  NHS 111, and 999 in an emergency.
- **No hard sell.** Lifestyle stories don't end in a pitch. The site's own pages do that.

## 5. From draft to published

1. **Draft** the story in Posts → Add New (or edit one of the launch drafts).
2. **Section:** tick one of the five categories.
3. **Standfirst:** fill in the Excerpt, one or two sentences. It appears on every card.
4. **Pharmacist review:** a pharmacist reads it against section 4, then sets
   *Written by* and *Reviewed by* in the post's clinical fields. Nothing is published
   without this.
5. **Photograph:** set a featured image: real, warm, everyday (food, walks, people
   living their lives), never a medicine. Landscape, at least 1200px wide.
6. **Publish.**

- **Leading the Hub:** the newest story leads automatically. To keep a particular
  story in the lead, tick *Stick to the top of the blog* in the post's publish settings.
- **The home page section** appears by itself once at least three stories are
  published, and always shows the newest (or stuck) story with the next three beside it.
  There is nothing to pick.
- **Read time** is worked out from the length. Set the post's *Reading time* field only
  to override it.

## 6. Rhythm

- Two stories a month, every month, beats ten in one week and then silence.
- At least one *Your treatment* story a month: it serves the patients we already have.
- Plan around the calendar: January fresh starts, spring walks, summer holidays,
  back-to-school routines, party season.

## 7. The launch set

Six stories, created as **drafts** on deploy, waiting for pharmacist review:

| Story | Section |
|---|---|
| Eat well for less | Eat well |
| Become a weight loss super sleuth | Eat well |
| Weight loss. The adventures are just beginning. | Real life |
| Exercise for people who'd rather not | Move more |
| When the scales stop moving | Mind & motivation |
| Eating well on a smaller appetite | Your treatment |

## 8. The next six

| Title | Section | Theme | The angle |
|---|---|---|---|
| Eating out without the maths | Real life | Making it harder than it needs to be | Menus, sharing plates, saying no politely; enjoying the evening |
| The weight-loss habit nobody talks about: sleep | Mind & motivation | Nobody told me | How short sleep drives hunger and cravings; a realistic wind-down |
| Five filling dinners from the store cupboard | Eat well | Easier than I thought | Protein-first meals from tins, frozen veg and pantry staples |
| Strong at any size: a ten-minute routine | Move more | Easier than I thought | Five bodyweight moves, a printable plan, safety checks |
| Holidays without the guilt | Real life | Wish I'd found this sooner | Enjoying the trip, staying active, picking back up at home |
| Your first month with us, week by week | Your treatment | Just needed someone to explain it | The service from assessment to delivery and check-ins; what to expect and when to get in touch. No medicine names. |

## 9. How it's built

| Piece | File |
|---|---|
| Sections, story data, covers, cards | `at-health-theme/inc/health-hub.php` |
| Home page section | `at-health-theme/template-parts/section-health-hub.php` |
| Health Hub page | `at-health-theme/page-templates/page-health-hub.php` |
| Article page (section link, read time, Keep reading) | `at-health-theme/single.php` |
| Launch drafts (one-off) | `at-health-theme/inc/health-hub-launch.php` |
| Shared styles | `globals.css` (*Health Hub — magazine components*) |

The home section's threshold can be changed with the `ah_health_hub_home_min_stories` filter.
