# healthy-fleet-preview
A place to share just a little of my proprietary codebase

Healthy Fleet is my own SaaS project built with Laravel/LiveWire/Volt. It provides companies that have fleets: 
- vehicle maintenance record keeping
- alerts when a vehicle is due for a service
- spending reports and statistics
- and more

When discussing with my initial client, I learned that one of the largest determining factors in choosing a solution like Heathy Fleet is how difficult is onboarding? So I built from the ground up with this in mind. 

## Uploading past services

Lets say you represent a company with 10 vehicles in your fleet, and you have been tracking the maintenance of each of them in a big binder from behind a desk. The binder is full of printed records and now you have to manually enter all the information into a software product and it'll take days. Unless you chose Healthy Fleet...

**past-service-from** is one instance of solving this digitization problem. In it I use another component I made called **file-to-form**, which  prompts a user to upload an image, pdf, or allows for just copy pasting a lot of text, and returns to **past-service-form** a form object filled with information from the user input. Using an LLM API the image is transcribed, (or the pdf is parsed in house) and the whole of the text is provided to the same LLM along an empty form object representing the desired data. 