<!DOCTYPE html> <!-- Declares the document type, indicating it's an HTML5 document -->
<html lang="en"> <!-- Opens the HTML document and sets the language to English -->
<head>
    <meta charset="UTF-8"> <!-- Sets the character encoding for the document to UTF-8 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Ensures the page is responsive on mobile devices -->
    <title>Text Classification App</title> <!-- Sets the title of the webpage that appears on the tab -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet"> <!-- Links to Bootstrap CSS for styling -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Links to jQuery library for easier DOM manipulation -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Links to Chart.js for creating charts -->
</head>
<body> <!-- Starts the body of the document -->
    <div class="container mt-5"> <!-- Creates a Bootstrap container with a top margin of 5 -->
        <h2 class="text-center">Text Classifications</h2> <!-- Centered header for the app -->

        <div class="mb-4"> <!-- Creates a bottom margin -->
            <label for="inputText" class="form-label">Enter text</label> <!-- Label for the textarea -->
            <textarea class="form-control" id="inputText" rows="4" placeholder="Type Your Content Here"></textarea> <!-- Textarea for user input -->
        </div>

        <div class="d-flex justify-content-center"> <!-- Flex container to center the button -->
            <button class="btn btn-primary" id="classifyBtn">Classify</button> <!-- Button to classify the input text -->
        </div>

        <div class="result-container" style="display: flex; flex-direction: column;"> <!-- Container for results -->
            <div class="mt-4"> <!-- Top margin for spacing -->
                <h4>Classification Result:</h4> <!-- Header for results -->
                <p id="result"></p> <!-- Paragraph to display the classification result -->
            </div>

            <div class="piechart-container" style="width: 300px !important; height: 300px !important;"> <!-- Container for pie chart -->
                <canvas id="resultChart" width="100px" height="100px"></canvas> <!-- Canvas for drawing the pie chart -->
            </div>
        </div>
    </div>

    <script> <!-- Starts the JavaScript section -->
        $(document).ready(function() { // Waits for the document to be fully loaded
            let chartInstance; // Variable to hold the chart instance
            const textareafield = document.getElementById("inputText"); // Gets the textarea element by its ID
            textareafield.addEventListener("focusout", function() { // Adds an event listener for when the textarea loses focus
                const limitedText = limitWords(textareafield.value, 104); // Calls limitWords function to limit text to 104 words
                textareafield.value = limitedText; // Updates the textarea value with the limited text
            });
            
            function limitWords(text, maxWords) { // Function to limit the number of words in the input
                const words = text.trim().split(/\s+/); // Splits the text into an array of words
                if (words.length > maxWords) { // Checks if the number of words exceeds maxWords
                    return words.slice(0, maxWords).join(" "); // Returns only the first maxWords words as a string
                }
                return text; // If within limit, return the original text
            }

            $('#classifyBtn').click(function() { // Adds a click event to the classify button
                var text = $('#inputText').val(); // Gets the value from the textarea

                $.post('process.php', { text: text }, function(data) { // Sends the text to process.php using POST method
                    var result = JSON.parse(data); // Parses the JSON response from the server
                    console.log(result); // Logs the result to the console for debugging

                    var topCategory = Object.keys(result)[0]; // Gets the first key (category) from the result
                    const maxKey = Object.keys(result).reduce((a, b) => (result[a] > result[b] ? a : b)); // Finds the category with the highest score

                    var resultHtml = "Predicted Category: <strong>" + maxKey + "</strong><br>Probabilities (Percentages):<br>"; // Prepares HTML for displaying results

                    var labels = []; // Array to hold category labels for the chart
                    var percentages = []; // Array to hold percentage values for the chart
                    for (var category in result) { // Loops through each category in the result
                        resultHtml += category + ": " + result[category].toFixed(2) + "%<br>"; // Adds category and its probability to the result HTML
                        labels.push(category); // Adds category label to the labels array
                        percentages.push((result[category]).toFixed(2)); // Adds probability to the percentages array
                    }

                    $('#result').html(resultHtml); // Updates the result paragraph with the generated HTML

                    var ctx = document.getElementById('resultChart').getContext('2d'); // Gets the context for the pie chart
                    if (chartInstance) { // Checks if a chart instance already exists
                        chartInstance.destroy(); // Destroys the previous chart instance
                    }
                    chartInstance = new Chart(ctx, { // Creates a new pie chart
                        type: 'pie', // Specifies the chart type
                        data: { // Data for the chart
                            labels: labels, // Uses the labels array for the chart labels
                            datasets: [{ // Dataset configuration
                                label: 'Category Probabilities', // Label for the dataset
                                data: percentages, // Uses the percentages array for the data
                                backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4caf50', '#f44336'] // Colors for each slice of the pie chart
                            }]
                        },
                        options: { // Options for customizing the chart
                            plugins: {
                                tooltip: { // Customizes tooltips displayed on hover
                                    callbacks: {
                                        label: function(context) { // Function to format the tooltip label
                                            return context.label + ': ' + context.raw + '%'; // Displays label and percentage in tooltip
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
            });
        });
    </script> <!-- Ends the JavaScript section -->
</body>
</html>


Summary of index.html:
This HTML file creates a user interface for a text classification application.
Users can enter text in a textarea and click a button to classify the text.
It displays the classification result and visualizes the probabilities as a pie chart.
The JavaScript code handles user interactions, communicates with the server, and updates the UI based on server responses.
Example Workflow:
A user types "The stock market is booming" in the textarea.
The user clicks the "Classify" button.
The text is sent to process.php, which processes it and sends back results.
The results are displayed below the button, and a pie chart visualizes the probabilities of different categories.



<?php // Starts the PHP code

// Include the dataset
$data = require 'data.php'; // Fetches the dataset from a separate file named data.php

// Function to preprocess text (tokenization, lowercasing)
function preprocess_text($text) {
    $text = strtolower($text); // Converts the text to lowercase
    $text = preg_replace('/[^a-z\s]/', '', $text); // Removes punctuation and non-alphabetic characters
    $words = explode(" ", $text); // Splits the cleaned text into an array of words
    return array_filter($words); // Removes any empty elements from the array
}

// Function to train the model
function train_naive_bayes($data) {
    $categories = []; // Array to hold the count of documents per category
    $vocab = []; // Array to hold the vocabulary of words
    $category_word_counts = []; // Array to hold word counts per category
    $category_doc_counts = []; // Array to hold document counts per category
    $total_docs = count($data); // Gets the total number of documents in the dataset

    foreach ($data as $item) { // Loops through each item in the dataset
        $category = $item['category']; // Gets the category of the current item
        $words = preprocess_text($item['article']); // Preprocesses the article text

        // Initialize category count
        if (!isset($categories[$category])) { // Checks if the category is not already set
            $categories[$category] = 0; // Initializes the count for this category to 0
            $category_word_counts[$category] = []; // Initializes an empty array for word counts in this category
            $category_doc_counts[$category] = 0; // Initializes the document count for this category
        }

        // Count documents per category
        $category_doc_counts[$category]++; // Increments the document count for the current category
        $categories[$category] += count($words); // Adds the number of words in the article to the category count

        foreach ($words as $word) { // Loops through each word in the article
            if (!isset($vocab[$word])) { // Checks if the word is not already in the vocabulary
                $vocab[$word] = 0; // Initializes the word count for this word to 0
            }
            $vocab[$word]++; // Increments the count of the word in the vocabulary
            
            if (!isset($category_word_counts[$category][$word])) { // Checks if the word count for this category is not set
                $category_word_counts[$category][$word] = 0; // Initializes the count for this word in the category to 0
            }
            $category_word_counts[$category][$word]++; // Increments the word count for this category
        }
    }

    // Returns the model as an array
    return [
        'categories' => $categories, // The count of words in each category
        'vocab' => $vocab, // The overall vocabulary of words
        'category_word_counts' => $category_word_counts, // Word counts per category
        'category_doc_counts' => $category_doc_counts, // Document counts per category
        'total_docs' => $total_docs // Total number of documents
    ];
}

// Function to normalize probabilities and convert them to percentages summing to 100%
function normalize_probabilities($scores) {
    $exp_scores = []; // Array to hold exponentiated scores
    $total = 0; // Variable to hold the sum of exponentiated scores

    // Calculate exponentiated probabilities
    foreach ($scores as $category => $score) { // Loops through each category and its score
        $exp_scores[$category] = exp($score); // Calculates the exponent of the score
        $total += exp($score); // Adds to the total
    }

    // Normalize to sum to 100%
    foreach ($exp_scores as $category => $exp_score) { // Loops through exponentiated scores
        $exp_scores[$category] = ($exp_score / $total) * 100; // Normalizes to percentage
    }

    return $exp_scores; // Returns the normalized probabilities
}

// Function to predict category
function classify_naive_bayes($text, $model) {
    $words = preprocess_text($text); // Preprocesses the input text
    $category_scores = []; // Array to hold scores for each category
    
    foreach ($model['categories'] as $category => $category_word_count) { // Loops through each category
        $log_prob = log($model['category_doc_counts'][$category] / $model['total_docs']); // Calculates log probability for the category
        
        foreach ($words as $word) { // Loops through each word in the input text
            // Gets the word count in the category, or 0 if it doesn't exist
            $word_count_in_category = $model['category_word_counts'][$category][$word] ?? 0; 
            // Calculates the log probability for the word
            $log_prob += log(($word_count_in_category + 1) / ($model['categories'][$category] + count($model['vocab']))); 
        }
        
        $category_scores[$category] = $log_prob; // Saves the log probability score for the category
    }

    // Normalize scores to return probabilities summing to 100%
    return normalize_probabilities($category_scores); // Returns the normalized probabilities
}

// Train the model
$model = train_naive_bayes($data); // Calls the train function with the dataset to create the model

// Get the predicted category (as JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Checks if the request method is POST
    $input_text = $_POST['text']; // Gets the input text from the POST request
    $predictions = classify_naive_bayes($input_text, $model); // Classifies the input text using the model
    echo json_encode($predictions); // Returns the predictions as a JSON string
}


Summary of process.php:
Starts with PHP: This script handles the backend logic of the application, receiving text input and returning classification results.
Data Handling: It loads a dataset of labeled text articles from data.php.
Preprocessing: The input text is converted to lowercase and cleaned up to remove punctuation.
Training the Model: A Naive Bayes model is created, counting word occurrences and documents per category.
Classification: The script classifies input text based on the trained model, calculating probabilities for each category.
Response: It returns the results in JSON format, which can be processed by the frontend JavaScript.
Example Workflow:
Input: The user submits the text "The stock market is booming."
Preprocessing: The text is cleaned to "the stock market is booming".
Model Training: The model counts occurrences of words in the training dataset and calculates probabilities for each category.
Classification: The model predicts probabilities, e.g.,
json

  {
    "business": 80.00,
    "sports": 15.00,
    "health": 5.00
}
  
Output: The predictions are sent back to the frontend, where they are displayed to the user.
Detailed Explanation for Understanding:
Frontend (index.html): Provides the user interface for input and results display. It uses JavaScript to send requests to the server and handle responses without reloading the page.
Backend (process.php): Processes the input text, trains the Naive Bayes model using a dataset, classifies the input, and returns results.
Model Mechanics: The Naive Bayes algorithm is based on calculating the probability of a category given the words present in the text. It assumes the presence of a word in a category is independent of the presence of any other word.
This detailed breakdown should help you understand how the text classification application works, from user input through to model training and classification, along with examples of expected outputs at each step. If you have any further questions or need clarification, feel free to ask!
